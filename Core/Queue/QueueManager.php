<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Queue\Contracts\JobInterface;
use Core\Queue\Contracts\QueueDriverInterface;
use Core\Queue\Contracts\QueueInterface;
use Core\Queue\Exceptions\InvalidDriverException;
use Core\Queue\Exceptions\QueueException;

/**
 * Point d'entrée unique du sous-système Queue.
 *
 * Responsabilités :
 *  - Enregistrement et résolution des drivers
 *  - Dispatch des Jobs (push / later / dispatchIf…)
 *  - Création des Workers
 *
 * Usage :
 *
 *   $manager = new QueueManager(defaultDriver: 'database');
 *   $manager->registerDriver('database', fn() => new DatabaseDriver($pdo));
 *   $manager->registerDriver('redis',    fn() => new RedisDriver($redis));
 *   $manager->registerDriver('sync',     fn() => new SyncDriver());
 *
 *   $manager->dispatch(new SendEmailJob($user));
 *   $manager->later(new GenerateReportJob($id), delaySeconds: 60);
 */
final class QueueManager implements QueueInterface
{
    /**
     * Factories des drivers enregistrés.
     * @var array<string, callable(): QueueDriverInterface>
     */
    private array $driverFactories = [];

    /**
     * Instances résolues (singleton par nom de driver).
     * @var array<string, QueueDriverInterface>
     */
    private array $resolvedDrivers = [];

    public function __construct(
        private readonly string $defaultDriver = 'database',
    ) {}

    // -------------------------------------------------------------------------
    // Enregistrement des drivers
    // -------------------------------------------------------------------------

    /**
     * Enregistre un driver de queue via une factory callable.
     *
     * @param callable(): QueueDriverInterface $factory
     */
    public function registerDriver(string $name, callable $factory): void
    {
        $this->driverFactories[$name] = $factory;

        // Si le driver était déjà résolu, on invalide le cache.
        unset($this->resolvedDrivers[$name]);
    }

    // -------------------------------------------------------------------------
    // QueueInterface — Dispatch
    // -------------------------------------------------------------------------

    /**
     * Dispatche un Job dans la queue appropriée.
     * Retourne l'identifiant unique du Job.
     *
     * @throws QueueException
     */
    public function dispatch(JobInterface $job): string
    {
        $payload = JobPayload::fromJob($job);
        $driver  = $this->driver();

        return $driver->push($payload, $payload->queue);
    }

    /**
     * Dispatche un Job avec un délai d'exécution.
     *
     * @throws QueueException
     */
    public function later(JobInterface $job, int $delaySeconds): string
    {
        if ($delaySeconds < 0) {
            throw new QueueException('Delay must be a positive integer.');
        }

        $payload = JobPayload::fromJob($job, $delaySeconds);
        $driver  = $this->driver();

        return $driver->later($payload, $payload->queue, $delaySeconds);
    }

    /**
     * Dispatche uniquement si la condition est vraie.
     *
     * @throws QueueException
     */
    public function dispatchIf(bool $condition, JobInterface $job): ?string
    {
        if (!$condition) {
            return null;
        }

        return $this->dispatch($job);
    }

    /**
     * Dispatche uniquement si la condition est fausse.
     *
     * @throws QueueException
     */
    public function dispatchUnless(bool $condition, JobInterface $job): ?string
    {
        return $this->dispatchIf(!$condition, $job);
    }

    // -------------------------------------------------------------------------
    // QueueInterface — Inspection
    // -------------------------------------------------------------------------

    public function size(string $queue = 'default'): int
    {
        return $this->driver()->size($queue);
    }

    // -------------------------------------------------------------------------
    // Résolution des drivers
    // -------------------------------------------------------------------------

    /**
     * Retourne le driver résolu (avec lazy instantiation).
     *
     * @throws InvalidDriverException
     */
    public function driver(?string $name = null): QueueDriverInterface
    {
        $name ??= $this->defaultDriver;

        if (isset($this->resolvedDrivers[$name])) {
            return $this->resolvedDrivers[$name];
        }

        if (!isset($this->driverFactories[$name])) {
            throw InvalidDriverException::forDriver($name);
        }

        $driver = ($this->driverFactories[$name])();

        if (!$driver instanceof QueueDriverInterface) {
            throw new QueueException(sprintf(
                'Driver factory for "%s" must return an instance of QueueDriverInterface.',
                $name,
            ));
        }

        return $this->resolvedDrivers[$name] = $driver;
    }

    // -------------------------------------------------------------------------
    // Création d'un Worker
    // -------------------------------------------------------------------------

    /**
     * Crée et retourne un Worker configuré pour ce manager.
     */
    public function createWorker(WorkerOptions $options, ?callable $logger = null): Worker
    {
        $driver = $this->driver($options->driver);

        return new Worker($driver, $options, $logger);
    }

    // -------------------------------------------------------------------------
    // Drivers enregistrés (diagnostic)
    // -------------------------------------------------------------------------

    /**
     * @return string[]
     */
    public function registeredDrivers(): array
    {
        return array_keys($this->driverFactories);
    }

    public function hasDriver(string $name): bool
    {
        return isset($this->driverFactories[$name]);
    }
}