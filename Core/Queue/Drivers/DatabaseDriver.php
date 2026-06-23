<?php

declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\Contracts\QueueDriverInterface;
use Core\Queue\Exceptions\QueueException;
use Core\Queue\JobPayload;

/**
 * Driver Database (PDO) pour la persistance des Jobs en base de données.
 *
 * Cycle de vie du champ `status` :
 *
 *   push()        → status = 'pending'
 *   pop()         → status = 'processing'  + reserved_at = NOW()
 *   acknowledge() → DELETE (status = 'done' n'est pas conservé en table)
 *   release()     → status = 'pending'     + reserved_at = NULL
 *   fail()        → DELETE jobs + INSERT failed_jobs avec status = 'failed'
 */
final class DatabaseDriver implements QueueDriverInterface
{
    /**
     * Durée de réservation atomique d'un Job (en secondes).
     * Passé ce délai, un Job réservé mais non traité redevient disponible.
     */
    private const RESERVATION_TIMEOUT = 90;

    public function __construct(
        private readonly \PDO   $pdo,
        private readonly string $table       = 'jobs',
        private readonly string $failedTable = 'failed_jobs',
    ) {
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    // -------------------------------------------------------------------------
    // QueueDriverInterface
    // -------------------------------------------------------------------------

    public function push(JobPayload $payload, string $queue): string
    {
        $data = $payload->toArray();

        $sql = sprintf(
            'INSERT INTO %s
             (id, queue, job_class, encoded_data, attempts, max_attempts,
              timeout, available_at, created_at, status, reserved_at)
             VALUES
             (:id, :queue, :job_class, :encoded_data, :attempts, :max_attempts,
              :timeout, :available_at, :created_at, :status, NULL)',
            $this->table,
        );

        $this->prepare($sql)->execute([
            'id'           => $data['id'],
            'queue'        => $queue,
            'job_class'    => $data['job_class'],
            'encoded_data' => $data['encoded_data'],
            'attempts'     => $data['attempts'],
            'max_attempts' => $data['max_attempts'],
            'timeout'      => $data['timeout'],
            'available_at' => $data['available_at'],
            'created_at'   => $data['created_at'],
            'status'       => JobPayload::STATUS_PENDING,
        ]);

        return $payload->id;
    }

    public function later(JobPayload $payload, string $queue, int $delaySeconds): string
    {
        // available_at est déjà calculé dans JobPayload::fromJob() avec le délai.
        return $this->push($payload, $queue);
    }

    /**
     * Réservation atomique via transaction + FOR UPDATE SKIP LOCKED.
     * Garantit qu'un Job n'est traité que par un seul Worker à la fois.
     */
    public function pop(string $queue): ?JobPayload
    {
        $now = time();

        $this->pdo->beginTransaction();

        try {
            // Libère d'abord les Jobs dont la réservation a expiré.
            $this->releaseExpiredReservations($queue, $now);

            // Sélectionne le prochain Job disponible avec verrouillage.
            $sql = sprintf(
                'SELECT * FROM %s
                 WHERE queue       = :queue
                   AND status      = :status
                   AND available_at <= :now
                 ORDER BY available_at ASC, created_at ASC
                 LIMIT 1
                 FOR UPDATE SKIP LOCKED',
                $this->table,
            );

            $stmt = $this->prepare($sql);
            $stmt->execute([
                'queue'  => $queue,
                'status' => JobPayload::STATUS_PENDING,
                'now'    => $now,
            ]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                $this->pdo->rollBack();
                return null;
            }

            // Marque le Job comme en cours de traitement.
            $update = sprintf(
                'UPDATE %s
                 SET status      = :status,
                     reserved_at = :reserved_at,
                     attempts    = attempts + 1
                 WHERE id = :id',
                $this->table,
            );

            $this->prepare($update)->execute([
                'status'      => JobPayload::STATUS_PROCESSING,
                'reserved_at' => $now,
                'id'          => $row['id'],
            ]);

            $this->pdo->commit();

            // Reflète les changements dans le payload retourné.
            $row['attempts']    += 1;
            $row['status']       = JobPayload::STATUS_PROCESSING;
            $row['reserved_at']  = (string) $now;
            
            return JobPayload::fromRaw($row);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new QueueException(
                'Failed to pop job from database queue: ' . $e->getMessage(), 0, $e
            );
        }
    }

    /**
     * Supprime le Job de la table après exécution réussie.
     * Le statut 'done' n'est pas conservé en table — le Job est simplement supprimé.
     */
    public function acknowledge(JobPayload $payload): void
    {
        $sql = sprintf('DELETE FROM %s WHERE id = :id', $this->table);
        $this->prepare($sql)->execute(['id' => $payload->id]);
    }

    /**
     * Remet le Job en queue pour un nouveau retry.
     * Repasse le statut à 'pending' et efface la réservation.
     */
    public function release(JobPayload $payload, int $delaySeconds = 0): void
    {
        $sql = sprintf(
            'UPDATE %s
             SET status       = :status,
                 reserved_at  = NULL,
                 available_at = :available_at,
                 attempts     = :attempts
             WHERE id = :id',
            $this->table,
        );

        $this->prepare($sql)->execute([
            'status'       => JobPayload::STATUS_PENDING,
            'available_at' => time() + $delaySeconds,
            'attempts'     => $payload->attempts,
            'id'           => $payload->id,
        ]);
    }

    /**
     * Déplace le Job dans la failed_jobs table avec status = 'failed'.
     */
    public function fail(JobPayload $payload, \Throwable $e): void
    {
        $this->pdo->beginTransaction();

        try {
            // Supprime de la queue principale.
            $delete = sprintf('DELETE FROM %s WHERE id = :id', $this->table);
            $this->prepare($delete)->execute(['id' => $payload->id]);

            // Insère dans la failed queue avec status explicite.
            $insert = sprintf(
                'INSERT INTO %s
                 (id, queue, job_class, encoded_data, attempts, status, failed_at, failed_reason)
                 VALUES
                 (:id, :queue, :job_class, :encoded_data, :attempts, :status, :failed_at, :failed_reason)',
                $this->failedTable,
            );

            $this->prepare($insert)->execute([
                'id'            => $payload->id,
                'queue'         => $payload->queue,
                'job_class'     => $payload->jobClass,
                'encoded_data'  => $payload->encodedData,
                'attempts'      => $payload->attempts,
                'status'        => JobPayload::STATUS_FAILED,
                'failed_at'     => time(),
                'failed_reason' => sprintf(
                    '[%s] %s in %s:%d',
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                ),
            ]);

            $this->pdo->commit();
        } catch (\Throwable $inner) {
            $this->pdo->rollBack();
            throw new QueueException(
                'Failed to record job failure: ' . $inner->getMessage(), 0, $inner
            );
        }
    }

    public function size(string $queue): int
    {
        $sql = sprintf(
            'SELECT COUNT(*) FROM %s WHERE queue = :queue AND status = :status',
            $this->table,
        );

        $stmt = $this->prepare($sql);
        $stmt->execute([
            'queue'  => $queue,
            'status' => JobPayload::STATUS_PENDING,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function flush(string $queue): void
    {
        $sql = sprintf('DELETE FROM %s WHERE queue = :queue', $this->table);
        $this->prepare($sql)->execute(['queue' => $queue]);
    }

    // -------------------------------------------------------------------------
    // Helpers internes
    // -------------------------------------------------------------------------

    /**
     * Remet en 'pending' les Jobs bloqués en 'processing' depuis trop longtemps.
     * Protège contre les Workers morts sans graceful shutdown.
     */
    private function releaseExpiredReservations(string $queue, int $now): void
    {
        $expiredBefore = $now - self::RESERVATION_TIMEOUT;

        $sql = sprintf(
            'UPDATE %s
             SET status      = :new_status,
                 reserved_at = NULL
             WHERE queue       = :queue
               AND status      = :current_status
               AND reserved_at IS NOT NULL
               AND reserved_at < :expired_before',
            $this->table,
        );

        $this->prepare($sql)->execute([
            'new_status'     => JobPayload::STATUS_PENDING,
            'queue'          => $queue,
            'current_status' => JobPayload::STATUS_PROCESSING,
            'expired_before' => $expiredBefore,
        ]);
    }

    /**
     * @throws QueueException
     */
    private function prepare(string $sql): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        if ($stmt === false) {
            throw new QueueException('Failed to prepare SQL statement.');
        }

        return $stmt;
    }
}