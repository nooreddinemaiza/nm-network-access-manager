<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Logger;
use Core\Queue\Contracts\QueueDriverInterface;

/**
 * Worker : processus CLI qui dépile et exécute les Jobs en continu.
 *
 * Fonctionnalités :
 *  - Graceful shutdown sur SIGTERM / SIGINT (termine le Job en cours avant de s'arrêter)
 *  - Timeout d'exécution par Job via pcntl_alarm()
 *  - Redémarrage automatique si la limite mémoire est atteinte
 *  - Limite optionnelle sur le nombre de Jobs ou la durée de vie du Worker
 *  - Backoff exponentiel en cas d'erreur de connexion au driver
 *  - Arrêt automatique quand la queue est vide (stopWhenEmpty)
 */
final class Worker
{
    /** Le Worker doit continuer à tourner. */
    private bool $shouldRun = true;

    /** Un signal d'arrêt a été reçu — on arrête après le Job en cours. */
    private bool $stopAfterCurrentJob = false;

    /** Un Job est en cours d'exécution. */
    private bool $jobRunning = false;

    /** Timeout du Job en cours (utilisé dans le handler SIGALRM). */
    private int $currentJobTimeout = 0;

    /** Nombre de Jobs traités depuis le démarrage. */
    private int $processedCount = 0;

    /** Timestamp de démarrage du Worker. */
    private int $startedAt;

    public function __construct(
        private readonly QueueDriverInterface $driver,
        private readonly WorkerOptions        $options,
    ) {
        $this->startedAt = time();
    }

    // -------------------------------------------------------------------------
    // Boucle principale
    // -------------------------------------------------------------------------

    /**
     * Démarre la boucle de traitement.
     * Cette méthode est bloquante jusqu'à ce que le Worker soit arrêté.
     */
    public function run(): void
    {
        // Désactive max_execution_time — obligatoire pour un Worker long-running.
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $this->registerSignalHandlers();

        $this->log('info', 'Worker started.', [
            'queue'         => $this->options->queue,
            'driver'        => $this->options->driver,
            'pid'           => getmypid(),
            'stopWhenEmpty' => $this->options->stopWhenEmpty,
        ]);

        while ($this->shouldRun) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            if ($this->shouldStop()) {
                break;
            }

            try {
                $payload = $this->driver->pop($this->options->queue);
            } catch (\Throwable $e) {
                $this->log('error', 'Failed to pop job from queue — retrying after backoff.', [
                    'error'   => $e->getMessage(),
                    'backoff' => $this->options->backoffOnError,
                ]);
                $this->sleep($this->options->backoffOnError);
                continue;
            }

            if ($payload === null) {
                /*
                 * Queue vide.
                 *
                 * stopWhenEmpty = true  → on sort proprement, le processus se termine.
                 * stopWhenEmpty = false → comportement daemon : on dort et on recheck.
                 */
                if ($this->options->stopWhenEmpty) {
                    $this->log('info', 'Queue is empty — stopping worker (stopWhenEmpty=true).', [
                        'processed' => $this->processedCount,
                    ]);
                    break;
                }

                $this->sleep($this->options->sleep);
                continue;
            }

            $this->process($payload);
        }

        $this->log('info', 'Worker stopped gracefully.', [
            'processed' => $this->processedCount,
            'uptime'    => time() - $this->startedAt,
        ]);
    }

    // -------------------------------------------------------------------------
    // Traitement d'un Job
    // -------------------------------------------------------------------------

    private function process(JobPayload $payload): void
    {
        $this->jobRunning        = true;
        $this->currentJobTimeout = $payload->timeout;

        // $this->log('debug', 'Processing job.', [
        //     'id'      => $payload->id,
        //     'class'   => $payload->jobClass,
        //     'attempt' => $payload->attempts,
        //     'queue'   => $payload->queue,
        //     'timeout' => $payload->timeout,
        // ]);

        $this->setAlarm($payload->timeout);

        try {
            $job = $payload->resolveJob();

            $job->handle();

            $this->driver->acknowledge($payload);
            $this->processedCount++;

            // $this->log('info', 'Job completed successfully.', [
            //     'id'    => $payload->id,
            //     'class' => $payload->jobClass,
            // ]);
        } catch (\Throwable $e) {
            $this->handleFailure($payload, $e);
        } finally {
            $this->clearAlarm();
            $this->currentJobTimeout = 0;
            $this->jobRunning        = false;

            if ($this->stopAfterCurrentJob) {
                $this->shouldRun = false;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Gestion des échecs et retries
    // -------------------------------------------------------------------------

    private function handleFailure(JobPayload $payload, \Throwable $e): void
    {
        $this->log('warning', 'Job failed.', [
            'id'      => $payload->id,
            'class'   => $payload->jobClass,
            'attempt' => $payload->attempts,
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        if ($payload->hasExceededMaxAttempts()) {
            $this->sendToFailedQueue($payload, $e);
            return;
        }

        $delaySeconds = $this->computeRetryDelay($payload);

        $this->log('info', 'Retrying job.', [
            'id'    => $payload->id,
            'delay' => $delaySeconds,
        ]);

        $this->driver->release($payload, $delaySeconds);
    }

    private function sendToFailedQueue(JobPayload $payload, \Throwable $e): void
    {
        $this->log('error', 'Job exceeded max attempts — sending to failed queue.', [
            'id'    => $payload->id,
            'class' => $payload->jobClass,
        ]);

        try {
            $job = $payload->resolveJob();
            $job->failed($e);
        } catch (\Throwable $hookException) {
            $this->log('error', 'Job::failed() hook threw an exception.', [
                'id'    => $payload->id,
                'error' => $hookException->getMessage(),
            ]);
        }

        $this->driver->fail($payload, $e);
    }

    private function computeRetryDelay(JobPayload $payload): int
    {
        try {
            $job = $payload->resolveJob();

            if ($job instanceof Job) {
                return $job->retryDelay($payload->attempts);
            }
        } catch (\Throwable) {
            // Fallback sur un délai fixe si le Job ne peut pas être résolu.
        }

        return 10;
    }

    // -------------------------------------------------------------------------
    // Conditions d'arrêt
    // -------------------------------------------------------------------------

    private function shouldStop(): bool
    {
        if ($this->memoryExceeded()) {
            $this->log('warning', 'Memory limit exceeded — stopping worker.', [
                'limit'   => $this->options->memory . ' Mo',
                'current' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' Mo',
            ]);
            return true;
        }

        if ($this->options->maxJobs > 0 && $this->processedCount >= $this->options->maxJobs) {
            $this->log('info', 'Max jobs reached — stopping worker.', [
                'max_jobs' => $this->options->maxJobs,
            ]);
            return true;
        }

        if ($this->options->maxTime > 0 && (time() - $this->startedAt) >= $this->options->maxTime) {
            $this->log('info', 'Max time reached — stopping worker.', [
                'max_time' => $this->options->maxTime,
            ]);
            return true;
        }

        return false;
    }

    private function memoryExceeded(): bool
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $this->options->memory;
    }

    // -------------------------------------------------------------------------
    // Signaux POSIX
    // -------------------------------------------------------------------------

    private function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            $this->log('warning', 'pcntl extension not available — graceful shutdown disabled.');
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function (): void {
            $this->log('info', 'SIGTERM received — graceful shutdown initiated.');
            $this->handleShutdownSignal();
        });

        pcntl_signal(SIGINT, function (): void {
            $this->log('info', 'SIGINT received — graceful shutdown initiated.');
            $this->handleShutdownSignal();
        });

        pcntl_signal(SIGALRM, function (): void {
            throw new \RuntimeException(sprintf(
                'Job timed out after %d seconds.',
                $this->currentJobTimeout,
            ));
        });
    }

    private function handleShutdownSignal(): void
    {
        if ($this->jobRunning) {
            $this->stopAfterCurrentJob = true;
            $this->log('info', 'Waiting for current job to finish before stopping...');
        } else {
            $this->shouldRun = false;
        }
    }

    // -------------------------------------------------------------------------
    // Timeout par Job (pcntl_alarm)
    // -------------------------------------------------------------------------

    private function setAlarm(int $timeoutSeconds): void
    {
        if ($timeoutSeconds > 0 && function_exists('pcntl_alarm')) {
            pcntl_alarm($timeoutSeconds);
        }
    }

    private function clearAlarm(): void
    {
        if (function_exists('pcntl_alarm')) {
            pcntl_alarm(0);
        }
    }

    // -------------------------------------------------------------------------
    // Utilitaires
    // -------------------------------------------------------------------------

    private function sleep(int $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        for ($i = 0; $i < $seconds; $i++) {
            if (!$this->shouldRun) {
                break;
            }
            sleep(1);
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $ctx = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        if (method_exists(Logger::class, $level)) {
            Logger::$level("{$message}{$ctx}");
        } else {
            Logger::warning(
                "{$level} is not a logging level! " .
                "Main message: {$message}{$ctx}"
            );
        }
    }
}