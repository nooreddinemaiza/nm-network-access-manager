<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\File;
use Core\Routing\RouteException;
use Core\System\Config;

/**
 * Configuration immuable du Worker.
 *
 * Passée au constructeur du Worker ou construite depuis les arguments CLI.
 */
final class WorkerOptions
{
    public function __construct(
        /** Nom de la queue à surveiller. */
        public ?string $queue = null,
        /** Nom du driver à utiliser (doit être enregistré dans QueueManager). */
        public ?string $driver = null,
        /** Délai d'attente en secondes quand la queue est vide (évite le busy-loop). */
        public ?int $sleep = null,
        /** Nombre maximum de Jobs à traiter avant de redémarrer le Worker (0 = illimité). */
        public ?int $maxJobs = null,
        /** Durée maximale de vie du Worker en secondes (0 = illimité). */
        public ?int $maxTime = null,
        /** Mémoire maximale en Mo avant redémarrage du Worker. */
        public ?int $memory = null,
        /** Délai entre chaque retry en cas d'erreur de connexion au driver. */
        public ?int $backoffOnError = null,
        /** Mode debug : log détaillé de chaque Job. */
        public ?bool $verbose = null,
        /**
         * Arrêt automatique dès que la queue est vide.
         *
         * true  → le Worker se termine proprement quand driver->pop() renvoie null.
         *         Idéal pour une exécution via cron : le cron lance le Worker,
         *         il traite tous les jobs disponibles, puis s'arrête seul.
         *
         * false → comportement daemon : le Worker dort (sleep secondes) puis recheck.
         *         Idéal pour une exécution via supervisord / systemd en continu.
         */
        public ?bool $stopWhenEmpty = null,
    ) {
        try {
            if (!File::is_readable('config', 'job.php')) {
                throw new RouteException();
            }
            $jobConfig    = Config::get('job');
            $defaultDriver = $jobConfig['default_driver'];
            $worker       = $jobConfig['worker'];

            $this->driver        = $driver        ?? $defaultDriver;
            $this->queue         = $queue         ?? $worker['default_queue'];
            $this->sleep         = $sleep         ?? $worker['sleep'];
            $this->maxJobs       = $maxJobs       ?? $worker['max-jobs'];
            $this->maxTime       = $maxTime       ?? $worker['max-time'];
            $this->memory        = $memory        ?? $worker['memory'];
            $this->verbose       = $verbose       ?? $worker['verbose'];
            $this->backoffOnError = $backoffOnError ?? $worker['backoff-on-error'];
            $this->stopWhenEmpty = $stopWhenEmpty  ?? $worker['stop-when-empty'];
        } catch (RouteException $e) {
            return RouteException::handleInternalServerError(exception: $e);
        }
    }

    /**
     * Construit une instance depuis un tableau (ex: arguments CLI parsés).
     *
     * @param array<string, mixed> $options
     */
    public static function fromArray(array $options): self
    {
        return new self(
            queue:          (string) ($options['queue']            ?? 'default'),
            driver:         (string) ($options['driver']           ?? 'database'),
            sleep:          (int)    ($options['sleep']            ?? 3),
            maxJobs:        (int)    ($options['max-jobs']         ?? 0),
            maxTime:        (int)    ($options['max-time']         ?? 0),
            memory:         (int)    ($options['memory']           ?? 128),
            backoffOnError: (int)    ($options['backoff-on-error'] ?? 5),
            verbose:        (bool)   ($options['verbose']          ?? false),
            stopWhenEmpty:  (bool)   ($options['stop-when-empty']  ?? false),
        );
    }
}