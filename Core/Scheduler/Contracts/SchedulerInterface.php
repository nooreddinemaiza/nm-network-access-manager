<?php

declare(strict_types=1);

namespace Core\Scheduler\Contracts;

use Core\Scheduler\Schedule;

interface SchedulerInterface
{
    /**
     * Enregistre les tâches planifiées de l'application.
     * Appelé une fois au démarrage du Scheduler.
     */
    public function schedule(Schedule $schedule): void;

    /**
     * Exécute toutes les tâches dont l'heure est venue.
     * Appelé par `php artisan schedule:run` (crontab toutes les minutes).
     */
    public function run(): void;
}