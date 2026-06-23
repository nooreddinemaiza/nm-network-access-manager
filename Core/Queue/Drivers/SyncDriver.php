<?php

declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\Contracts\QueueDriverInterface;
use Core\Queue\Exceptions\JobException;
use Core\Queue\JobPayload;

/**
 * Driver synchrone : exécute le Job immédiatement dans le même processus.
 *
 * Idéal pour les environnements de développement et les tests.
 * Ignore les délais (later()) et les retries.
 */
final class SyncDriver implements QueueDriverInterface
{
    /** @var JobPayload[] Historique des Jobs exécutés (utile pour les tests). */
    private array $processed = [];

    /** @var JobPayload[] Jobs échoués en mémoire. */
    private array $failed = [];

    public function push(JobPayload $payload, string $queue): string
    {
        $this->execute($payload);

        return $payload->id;
    }

    public function later(JobPayload $payload, string $queue, int $delaySeconds): string
    {
        // Le driver Sync ignore les délais — exécution immédiate.
        return $this->push($payload, $queue);
    }

    public function pop(string $queue): ?JobPayload
    {
        // Le driver Sync n'a pas de queue persistante.
        return null;
    }

    public function acknowledge(JobPayload $payload): void
    {
        // Rien à faire : le Job a déjà été exécuté dans push().
    }

    public function release(JobPayload $payload, int $delaySeconds = 0): void
    {
        // En mode sync, on ré-exécute immédiatement (comportement de retry).
        $this->execute($payload->incrementAttempts());
    }

    public function fail(JobPayload $payload, \Throwable $e): void
    {
        $this->failed[] = $payload->withFailedReason(
            sprintf('%s: %s', get_class($e), $e->getMessage())
        );
    }

    public function size(string $queue): int
    {
        return 0; // Aucune persistance en mode sync.
    }

    public function flush(string $queue): void
    {
        $this->processed = [];
        $this->failed    = [];
    }

    // -------------------------------------------------------------------------
    // Accesseurs utiles pour les tests
    // -------------------------------------------------------------------------

    /** @return JobPayload[] */
    public function getProcessed(): array
    {
        return $this->processed;
    }

    /** @return JobPayload[] */
    public function getFailed(): array
    {
        return $this->failed;
    }

    // -------------------------------------------------------------------------
    // Exécution interne
    // -------------------------------------------------------------------------

    private function execute(JobPayload $payload): void
    {
        try {
            $job = $payload->resolveJob();
            $job->handle();
            $this->processed[] = $payload;
        } catch (\Throwable $e) {
            $this->fail($payload, $e);

            // En mode sync, on propage l'exception pour ne pas masquer les erreurs.
            throw new JobException(
                sprintf('Sync job "%s" failed: %s', $payload->jobClass, $e->getMessage()),
                $payload,
                $e,
            );
        }
    }
}