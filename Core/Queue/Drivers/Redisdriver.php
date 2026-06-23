<?php

declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\Contracts\QueueDriverInterface;
use Core\Queue\Exceptions\QueueException;
use Core\Queue\JobPayload;

/**
 * Driver Redis pour les queues haute performance.
 *
 * Architecture des clés Redis :
 *   queues:{name}            → Liste des Jobs en attente (LPUSH / BRPOPLPUSH)
 *   queues:{name}:reserved   → Liste des Jobs réservés (en cours de traitement)
 *   queues:{name}:delayed    → Sorted Set pour les Jobs différés (score = timestamp)
 *   queues:failed            → Liste des Jobs définitivement échoués
 *   jobs:{id}                → Hash contenant le payload complet du Job
 *
 * Nécessite l'extension PHP `redis` (pecl install redis).
 * Compatible Redis 5+.
 */
final class RedisDriver implements QueueDriverInterface
{
    /** Durée de réservation en secondes avant qu'un Job soit remis en queue. */
    private const RESERVATION_TIMEOUT = 90;

    /** Préfixe global de toutes les clés Redis de ce driver. */
    private const KEY_PREFIX = 'queues';

    public function __construct(
        private readonly \Redis $redis,
    ) {}

    // -------------------------------------------------------------------------
    // QueueDriverInterface
    // -------------------------------------------------------------------------

    public function push(JobPayload $payload, string $queue): string
    {
        $this->storePayload($payload);

        // LPUSH → le Worker lit avec BRPOPLPUSH (FIFO de droite à gauche).
        $this->redis->lPush($this->key($queue), $payload->id);

        return $payload->id;
    }

    public function later(JobPayload $payload, string $queue, int $delaySeconds): string
    {
        $this->storePayload($payload);

        // Sorted set : score = timestamp de disponibilité
        $this->redis->zAdd(
            $this->key($queue, 'delayed'),
            (float) $payload->availableAt,
            $payload->id,
        );

        return $payload->id;
    }

    public function pop(string $queue): ?JobPayload
    {
        // Migre d'abord les Jobs différés dont le délai est écoulé.
        $this->migrateDelayedJobs($queue);

        // Libère les réservations expirées.
        $this->releaseExpiredReservations($queue);

        // Déplace atomiquement le Job de la queue principale vers :reserved.
        $id = $this->redis->rPopLPush(
            $this->key($queue),
            $this->key($queue, 'reserved'),
        );

        if ($id === false || $id === null) {
            return null;
        }

        $payload = $this->loadPayload((string) $id);

        if ($payload === null) {
            // Le payload a disparu (incohérence) — on nettoie la réservation.
            $this->redis->lRem($this->key($queue, 'reserved'), (string) $id, 1);
            return null;
        }

        // Incrémente le compteur de tentatives.
        $payload = $payload->incrementAttempts();
        $this->storePayload($payload);

        // Enregistre le timestamp de réservation pour détecter les timeouts.
        $this->redis->hSet(
            $this->reservationTimestampKey(),
            $payload->id,
            (string) time(),
        );

        return $payload;
    }

    public function acknowledge(JobPayload $payload): void
    {
        // Supprime de la liste :reserved
        $this->redis->lRem($this->key($payload->queue, 'reserved'), $payload->id, 1);

        // Supprime le timestamp de réservation
        $this->redis->hDel($this->reservationTimestampKey(), $payload->id);

        // Supprime le payload
        $this->redis->del($this->jobKey($payload->id));
    }

    public function release(JobPayload $payload, int $delaySeconds = 0): void
    {
        // Supprime de :reserved
        $this->redis->lRem($this->key($payload->queue, 'reserved'), $payload->id, 1);
        $this->redis->hDel($this->reservationTimestampKey(), $payload->id);

        // Remet en queue avec ou sans délai
        $this->storePayload($payload);

        if ($delaySeconds > 0) {
            $this->redis->zAdd(
                $this->key($payload->queue, 'delayed'),
                (float) (time() + $delaySeconds),
                $payload->id,
            );
        } else {
            $this->redis->lPush($this->key($payload->queue), $payload->id);
        }
    }

    public function fail(JobPayload $payload, \Throwable $e): void
    {
        // Supprime de :reserved
        $this->redis->lRem($this->key($payload->queue, 'reserved'), $payload->id, 1);
        $this->redis->hDel($this->reservationTimestampKey(), $payload->id);

        // Construit le payload d'échec
        $failedPayload = $payload->withFailedReason(sprintf(
            '[%s] %s in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        ));

        // Stocke dans la failed list
        $this->storePayload($failedPayload);
        $this->redis->lPush(self::KEY_PREFIX . ':failed', $payload->id);
    }

    public function size(string $queue): int
    {
        return (int) $this->redis->lLen($this->key($queue));
    }

    public function flush(string $queue): void
    {
        $this->redis->del(
            $this->key($queue),
            $this->key($queue, 'reserved'),
            $this->key($queue, 'delayed'),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers internes
    // -------------------------------------------------------------------------

    /**
     * Migre les Jobs différés dont le timestamp est dépassé vers la queue principale.
     */
    private function migrateDelayedJobs(string $queue): void
    {
        $delayedKey = $this->key($queue, 'delayed');
        $now        = time();

        // Récupère tous les IDs avec score <= maintenant
        $ids = $this->redis->zRangeByScore($delayedKey, '-inf', (string) $now);

        if (empty($ids)) {
            return;
        }

        // Script Lua atomique : zrem + lpush en une seule opération
        $script = <<<'LUA'
        local delayed = KEYS[1]
        local queue   = KEYS[2]
        local now     = tonumber(ARGV[1])

        local items = redis.call('ZRANGEBYSCORE', delayed, '-inf', now)

        for _, id in ipairs(items) do
            redis.call('ZREM',  delayed, id)
            redis.call('LPUSH', queue,   id)
        end

        return #items
        LUA;

        $this->redis->eval($script, [$delayedKey, $this->key($queue), $now], 2);
    }

    /**
     * Remet en queue les Jobs réservés dont le timeout est dépassé.
     */
    private function releaseExpiredReservations(string $queue): void
    {
        $reservedKey   = $this->key($queue, 'reserved');
        $timestampKey  = $this->reservationTimestampKey();
        $expiredBefore = time() - self::RESERVATION_TIMEOUT;

        $ids = $this->redis->lRange($reservedKey, 0, -1);

        foreach ($ids as $id) {
            $reservedAt = $this->redis->hGet($timestampKey, $id);

            if ($reservedAt !== false && (int) $reservedAt < $expiredBefore) {
                $script = <<<'LUA'
                local reserved = KEYS[1]
                local queue    = KEYS[2]
                local id       = ARGV[1]

                local removed = redis.call('LREM', reserved, 1, id)
                if removed > 0 then
                    redis.call('LPUSH', queue, id)
                end
                LUA;

                $this->redis->eval($script, [$reservedKey, $this->key($queue), $id], 2);
                $this->redis->hDel($timestampKey, $id);
            }
        }
    }

    /**
     * Stocke le payload complet dans un Hash Redis.
     */
    private function storePayload(JobPayload $payload): void
    {
        $this->redis->hMSet($this->jobKey($payload->id), $payload->toArray());
    }

    /**
     * Charge un payload depuis Redis.
     */
    private function loadPayload(string $id): ?JobPayload
    {
        $data = $this->redis->hGetAll($this->jobKey($id));

        if (empty($data)) {
            return null;
        }

        return JobPayload::fromRaw($data);
    }

    private function key(string $queue, string $suffix = ''): string
    {
        $key = self::KEY_PREFIX . ':' . $queue;

        return $suffix !== '' ? $key . ':' . $suffix : $key;
    }

    private function jobKey(string $id): string
    {
        return 'jobs:' . $id;
    }

    private function reservationTimestampKey(): string
    {
        return self::KEY_PREFIX . ':reservation_timestamps';
    }
}string $suffix = ''): string
    {
        $key = self::KEY_PREFIX . ':' . $queue;

        return $suffix !== '' ? $key . ':' . $suffix : $key;
    }

    private function jobKey(string $id): string
    {
        return 'jobs:' . $id;
    }

    private function reservationTimestampKey(): string
    {
        return self::KEY_PREFIX . ':reservation_timestamps';
    }
}