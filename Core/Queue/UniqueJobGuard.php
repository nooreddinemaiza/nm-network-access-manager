<?php

declare(strict_types=1);

namespace Core\Queue;

/**
 * Garde un verrou en base de données pour empêcher le double dispatch d'un Job.
 *
 * Utilise une table légère `job_locks` :
 *
 *   CREATE TABLE job_locks (
 *       lock_key    VARCHAR(191) PRIMARY KEY,
 *       locked_at   INT UNSIGNED NOT NULL,
 *       expires_at  INT UNSIGNED NOT NULL
 *   );
 *
 * Usage :
 *
 *   $guard = new UniqueJobGuard($pdo);
 *
 *   if ($guard->acquire('dns-logs:2025-01-15')) {
 *       $manager->dispatch(new DispatchDnsLogsJob('2025-01-15'));
 *   }
 *
 *   // Libérer manuellement si le Job échoue définitivement :
 *   $guard->release('dns-logs:2025-01-15');
 */
final class UniqueJobGuard
{
    /** Durée de vie par défaut d'un verrou en secondes (24h). */
    private const DEFAULT_TTL = 86400;

    public function __construct(
        private readonly \PDO   $pdo,
        private readonly string $table = 'job_locks',
    ) {
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    // -------------------------------------------------------------------------
    // API publique
    // -------------------------------------------------------------------------

    /**
     * Tente d'acquérir un verrou pour la clé donnée.
     *
     * Retourne true si le verrou a été posé (Job pas encore dispatché).
     * Retourne false si le verrou existe déjà et n'a pas expiré.
     *
     * @param string $key    Clé unique (ex: "dns-logs:2025-01-15")
     * @param int    $ttl    Durée de vie du verrou en secondes
     */
    public function acquire(string $key, int $ttl = self::DEFAULT_TTL): bool
    {
        $now       = time();
        $expiresAt = $now + $ttl;

        // Nettoie d'abord les verrous expirés pour cette clé.
        $this->purgeExpired($key, $now);

        // Tente une insertion atomique (PRIMARY KEY empêche les doublons).
        try {
            $sql  = sprintf(
                'INSERT INTO %s (lock_key, locked_at, expires_at) VALUES (:key, :locked_at, :expires_at)',
                $this->table,
            );
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'key'        => $key,
                'locked_at'  => $now,
                'expires_at' => $expiresAt,
            ]);

            return true;
        } catch (\PDOException $e) {
            // Violation de clé primaire = verrou déjà actif.
            if ($this->isDuplicateKeyException($e)) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Libère un verrou manuellement.
     * À appeler dans le hook Job::failed() si le Job échoue définitivement.
     */
    public function release(string $key): void
    {
        $sql  = sprintf('DELETE FROM %s WHERE lock_key = :key', $this->table);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['key' => $key]);
    }

    /**
     * Vérifie si un verrou actif existe pour cette clé.
     */
    public function isLocked(string $key): bool
    {
        $sql  = sprintf(
            'SELECT COUNT(*) FROM %s WHERE lock_key = :key AND expires_at > :now',
            $this->table,
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['key' => $key, 'now' => time()]);

        return (int) $stmt->fetchColumn() > 0;
    }

    // -------------------------------------------------------------------------
    // Helpers internes
    // -------------------------------------------------------------------------

    private function purgeExpired(string $key, int $now): void
    {
        $sql  = sprintf(
            'DELETE FROM %s WHERE lock_key = :key AND expires_at <= :now',
            $this->table,
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['key' => $key, 'now' => $now]);
    }

    private function isDuplicateKeyException(\PDOException $e): bool
    {
        // MySQL / MariaDB : SQLSTATE 23000, error code 1062
        // PostgreSQL      : SQLSTATE 23505
        $sqlState = $e->getCode();

        return in_array($sqlState, ['23000', '23505'], strict: true);
    }
}