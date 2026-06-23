<?php

declare(strict_types=1);

namespace Core\Queue\Contracts;

use Core\Queue\JobPayload;

interface QueueDriverInterface
{
    /**
     * Pousse un Job sérialisé dans la queue.
     */
    public function push(JobPayload $payload, string $queue): string;

    /**
     * Pousse un Job avec un délai d'exécution différé.
     */
    public function later(JobPayload $payload, string $queue, int $delaySeconds): string;

    /**
     * Réserve atomiquement le prochain Job disponible.
     * Retourne null si la queue est vide.
     */
    public function pop(string $queue): ?JobPayload;

    /**
     * Marque un Job comme terminé avec succès et le supprime.
     */
    public function acknowledge(JobPayload $payload): void;

    /**
     * Remet un Job en queue pour un nouveau retry.
     */
    public function release(JobPayload $payload, int $delaySeconds = 0): void;

    /**
     * Envoie un Job vers la failed queue de façon permanente.
     */
    public function fail(JobPayload $payload, \Throwable $e): void;

    /**
     * Retourne le nombre de Jobs en attente dans une queue.
     */
    public function size(string $queue): int;

    /**
     * Vide complètement une queue.
     */
    public function flush(string $queue): void;
}