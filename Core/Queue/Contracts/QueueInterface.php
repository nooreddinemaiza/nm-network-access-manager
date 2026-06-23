<?php

declare(strict_types=1);

namespace Core\Queue\Contracts;

interface QueueInterface
{
    /**
     * Dispatche un Job immédiatement dans la queue.
     */
    public function dispatch(JobInterface $job): string;

    /**
     * Dispatche un Job avec un délai d'exécution.
     */
    public function later(JobInterface $job, int $delaySeconds): string;

    /**
     * Dispatche un Job seulement si la condition est vraie.
     */
    public function dispatchIf(bool $condition, JobInterface $job): ?string;

    /**
     * Dispatche un Job seulement si la condition est fausse.
     */
    public function dispatchUnless(bool $condition, JobInterface $job): ?string;

    /**
     * Retourne le driver actif par son nom (ou le driver par défaut).
     */
    public function driver(?string $name = null): QueueDriverInterface;

    /**
     * Retourne la taille d'une queue.
     */
    public function size(string $queue = 'default'): int;
}