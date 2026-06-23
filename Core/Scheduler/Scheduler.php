<?php

declare(strict_types=1);

namespace Core\Scheduler;

use Core\Logger;
use Core\Scheduler\Contracts\SchedulerInterface;

/**
 * Exécuteur du Scheduler.
 *
 * Le crontab système ne contient plus qu'une seule ligne :
 *   * * * * * php /var/www/artisan schedule:run >> /dev/null 2>&1
 *
 * C'est le Scheduler qui décide quelles tâches sont dues à chaque minute.
 *
 * Usage (dans artisan schedule:run) :
 *
 *   $scheduler = new Scheduler();
 *   $scheduler->schedule($schedule);   // enregistre les tâches de l'application
 *   $scheduler->run();                 // exécute les tâches dues maintenant
 */
final class Scheduler implements SchedulerInterface
{
    private Schedule $schedule;

    public function __construct() {
        $this->schedule = new Schedule();
    }

    // -------------------------------------------------------------------------
    // SchedulerInterface
    // -------------------------------------------------------------------------

    public function schedule(Schedule $schedule): void
    {
        $this->schedule = $schedule;
    }

    /**
     * Exécute toutes les tâches dues à l'instant présent.
     */
    public function run(): void
    {
        $now  = new \DateTimeImmutable();
        $due  = $this->schedule->dueNow($now);

        if (empty($due)) {
            $this->log('debug', 'No tasks due.', ['time' => $now->format('Y-m-d H:i')]);
            return;
        }

        foreach ($due as $task) {
            $this->runTask($task, $now);
        }
    }

    // -------------------------------------------------------------------------
    // Exécution d'une tâche individuelle
    // -------------------------------------------------------------------------

    private function runTask(ScheduledTask $task, \DateTimeImmutable $now): void
    {
        $description = $task->getDescription() ?: $task->getCronExpression();

        $this->log('info', sprintf('Running scheduled task: %s', $description), [
            'time' => $now->format('Y-m-d H:i'),
            'cron' => $task->getCronExpression(),
        ]);

        $startedAt = microtime(true);

        try {
            $task->execute();

            $elapsed = round((microtime(true) - $startedAt) * 1000, 2);

            $this->log('info', sprintf('Task completed: %s', $description), [
                'elapsed_ms' => $elapsed,
            ]);
        } catch (\Throwable $e) {
            $this->log('error', sprintf('Task failed: %s', $description), [
                'error' => $e->getMessage(),
                'file'  => $e->getFile() . ':' . $e->getLine(),
            ]);

            // On ne propage pas l'exception : une tâche échouée ne doit pas
            // bloquer l'exécution des tâches suivantes.
        }
    }

    // -------------------------------------------------------------------------
    // Logger
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $context */
    private function log(string $level, string $message, array $context = []): void
    {

        $ctx = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);

        Logger::$level(sprintf(
            $level === 'error' ? STDERR : STDOUT,
            "[%s] [SCHEDULER] [%s] %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $ctx,
        ));
    }
}