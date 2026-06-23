<?php

declare(strict_types=1);

namespace Core\Scheduler;

/**
 * Représente une tâche planifiée individuelle.
 *
 * Méthodes de planification disponibles :
 *
 *   ->cron('0 2 * * *')      Expression cron complète
 *   ->daily()                Tous les jours à 00:00
 *   ->dailyAt('02:00')       Tous les jours à 02:00
 *   ->hourly()               Toutes les heures (minute 0)
 *   ->everyMinute()          Toutes les minutes
 *   ->weeklyOn(1, '08:00')   Tous les lundis à 08:00
 *   ->monthlyOn(1, '00:00')  Le 1er du mois à 00:00
 */
final class ScheduledTask
{
    private string  $cronExpression = '* * * * *';
    private string  $description    = '';
    private bool    $runInBackground = false;

    public function __construct(
        private readonly mixed $callback,
    ) {}

    // -------------------------------------------------------------------------
    // Helpers de planification (fluent)
    // -------------------------------------------------------------------------

    public function cron(string $expression): static
    {
        $this->validateCron($expression);
        $this->cronExpression = $expression;

        return $this;
    }

    public function everyMinute(): static
    {
        return $this->cron('* * * * *');
    }

    public function hourly(): static
    {
        return $this->cron('0 * * * *');
    }

    public function daily(): static
    {
        return $this->cron('0 0 * * *');
    }

    /**
     * @param string $time Format "HH:MM"
     */
    public function dailyAt(string $time): static
    {
        [$hour, $minute] = $this->parseTime($time);

        return $this->cron(sprintf('%d %d * * *', $minute, $hour));
    }

    /**
     * @param int    $dayOfWeek 0 (dimanche) à 6 (samedi)
     * @param string $time      Format "HH:MM"
     */
    public function weeklyOn(int $dayOfWeek, string $time = '00:00'): static
    {
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new \InvalidArgumentException('Day of week must be between 0 (Sunday) and 6 (Saturday).');
        }

        [$hour, $minute] = $this->parseTime($time);

        return $this->cron(sprintf('%d %d * * %d', $minute, $hour, $dayOfWeek));
    }

    /**
     * @param int    $day  Jour du mois (1–28 pour compatibilité tous les mois)
     * @param string $time Format "HH:MM"
     */
    public function monthlyOn(int $day = 1, string $time = '00:00'): static
    {
        if ($day < 1 || $day > 28) {
            throw new \InvalidArgumentException('Day must be between 1 and 28.');
        }

        [$hour, $minute] = $this->parseTime($time);

        return $this->cron(sprintf('%d %d %d * *', $minute, $hour, $day));
    }

    // -------------------------------------------------------------------------
    // Métadonnées
    // -------------------------------------------------------------------------

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function runInBackground(): static
    {
        $this->runInBackground = true;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Exécution
    // -------------------------------------------------------------------------

    /**
     * Vérifie si la tâche est due pour le moment donné.
     */
    public function isDue(\DateTimeImmutable $now): bool
    {
        return $this->matchesCron($this->cronExpression, $now);
    }

    /**
     * Exécute le callback de la tâche.
     */
    public function execute(): void
    {
        ($this->callback)();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCronExpression(): string
    {
        return $this->cronExpression;
    }

    public function shouldRunInBackground(): bool
    {
        return $this->runInBackground;
    }

    // -------------------------------------------------------------------------
    // Parsing cron interne
    // -------------------------------------------------------------------------

    /**
     * Vérifie si l'expression cron correspond au dateTimeImmutable donné.
     *
     * Supporte : valeurs fixes (5), wildcards (*), listes (1,3,5), plages (1-5), pas (*5).
     */
    private function matchesCron(string $expression, \DateTimeImmutable $now): bool
    {
        $parts = explode(' ', trim($expression));

        if (count($parts) !== 5) {
            return false;
        }

        [$minute, $hour, $dayOfMonth, $month, $dayOfWeek] = $parts;

        return $this->fieldMatches($minute,     (int) $now->format('i'))
            && $this->fieldMatches($hour,       (int) $now->format('G'))
            && $this->fieldMatches($dayOfMonth, (int) $now->format('j'))
            && $this->fieldMatches($month,      (int) $now->format('n'))
            && $this->fieldMatches($dayOfWeek,  (int) $now->format('w'));
    }

    private function fieldMatches(string $field, int $value): bool
    {
        // Wildcard
        if ($field === '*') {
            return true;
        }

        // Pas : */5, */15…
        if (str_starts_with($field, '*/')) {
            $step = (int) substr($field, 2);
            return $step > 0 && $value % $step === 0;
        }

        // Liste : 1,3,5
        if (str_contains($field, ',')) {
            return in_array($value, array_map('intval', explode(',', $field)), strict: true);
        }

        // Plage : 9-17
        if (str_contains($field, '-')) {
            [$from, $to] = explode('-', $field, 2);
            return $value >= (int) $from && $value <= (int) $to;
        }

        // Valeur fixe
        return (int) $field === $value;
    }

    /**
     * @return int[] [hour, minute]
     */
    private function parseTime(string $time): array
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid time format "%s". Expected "HH:MM".', $time)
            );
        }

        $hour   = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour > 23 || $minute > 59) {
            throw new \InvalidArgumentException(
                sprintf('Invalid time "%s": hour must be 0–23, minute 0–59.', $time)
            );
        }

        return [$hour, $minute];
    }

    private function validateCron(string $expression): void
    {
        $parts = explode(' ', trim($expression));

        if (count($parts) !== 5) {
            throw new \InvalidArgumentException(
                sprintf('Invalid cron expression "%s": must have exactly 5 fields.', $expression)
            );
        }
    }
}