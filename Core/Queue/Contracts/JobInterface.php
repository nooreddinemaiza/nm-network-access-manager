<?php

declare(strict_types=1);

namespace Core\Queue\Contracts;

interface JobInterface
{
    /**
     * Exécute la logique métier du Job.
     */
    public function handle(): void;

    /**
     * Appelé quand le Job échoue définitivement (après tous les retries).
     */
    public function failed(\Throwable $e): void;

    /**
     * Nombre maximum de tentatives avant d'envoyer en failed queue.
     */
    public function maxAttempts(): int;

    /**
     * Délai en secondes entre chaque retry (backoff fixe).
     * Retourner un tableau active le backoff exponentiel : [1, 5, 30]
     *
     * @return int|int[]
     */
    public function backoff(): int|array;

    /**
     * Timeout d'exécution en secondes (0 = pas de limite).
     */
    public function timeout(): int;

    /**
     * Queue cible sur laquelle ce Job doit être dispatché.
     */
    public function queue(): string;
}