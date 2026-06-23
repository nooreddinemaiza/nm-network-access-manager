<?php

declare(strict_types=1);

namespace Core\Scheduler;

/**
 * Représente une tâche planifiée avec son expression cron et son callback.
 *
 * Usage :
 *
 *   $schedule->call(fn() => $manager->dispatch(new MyJob()))
 *            ->dailyAt('02:00')
 *            ->description('Process DNS logs');
 *
 *   $schedule->call(fn() => ...)
 *            ->cron('0 2 * * *');
 */
final class Schedule
{
    /** @var ScheduledTask[] */
    private array $tasks = [];

    /**
     * Enregistre une tâche planifiée depuis un callable.
     */
    public function call(callable $callback): ScheduledTask
    {
        $task          = new ScheduledTask($callback);
        $this->tasks[] = $task;

        return $task;
    }

    /**
     * Retourne les tâches dont l'expression cron correspond au moment actuel.
     *
     * @return ScheduledTask[]
     */
    public function dueNow(\DateTimeImmutable $now): array
    {
        return array_filter(
            $this->tasks,
            fn(ScheduledTask $task) => $task->isDue($now),
        );
    }

    /** @return ScheduledTask[] */
    public function all(): array
    {
        return $this->tasks;
    }
}