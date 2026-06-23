<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Queue\Contracts\JobInterface;

/**
 * Classe de base pour tous les Jobs du framework.
 *
 * Les Jobs applicatifs étendent cette classe et implémentent handle().
 * Toutes les valeurs par défaut sont surchargeables par propriété ou méthode.
 *
 * Exemple minimal :
 *
 *   class SendEmailJob extends Job
 *   {
 *       public function __construct(
 *           protected string $to,
 *           protected string $subject,
 *       ) {}
 *
 *       public function handle(): void
 *       {
 *           // ... envoyer l'email
 *       }
 *   }
 */
abstract class Job implements JobInterface
{
    /**
     * Nombre maximum de tentatives avant envoi en failed queue.
     * Surcharger cette propriété dans la classe fille pour changer la valeur.
     */
    protected int $maxAttempts = 3;

    /**
     * Délai(s) en secondes entre chaque retry.
     * Tableau = backoff exponentiel : [10, 30, 60]
     *
     * @var int|int[]
     */
    protected int|array $backoff = 10;

    /**
     * Timeout d'exécution en secondes. 0 = pas de limite.
     */
    protected int $timeout = 60;

    /**
     * Nom de la queue cible.
     */
    protected string $onQueue = 'default';

    // -------------------------------------------------------------------------
    // Implémentation de JobInterface
    // -------------------------------------------------------------------------

    public function failed(\Throwable $e): void
    {
        // Hook vide par défaut. Surcharger pour envoyer une alerte, logger, etc.
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * @return int|int[]
     */
    public function backoff(): int|array
    {
        return $this->backoff;
    }

    public function timeout(): int
    {
        return $this->timeout;
    }

    public function queue(): string
    {
        return $this->onQueue;
    }

    // -------------------------------------------------------------------------
    // Fluent setters (utiles au moment du dispatch)
    // -------------------------------------------------------------------------

    /**
     * Définit la queue cible au moment du dispatch.
     *
     *   dispatch((new SendEmailJob($to))->onQueue('emails'))
     */
    public function onQueue(string $queue): static
    {
        $this->onQueue = $queue;

        return $this;
    }

    /**
     * Définit le nombre de tentatives au moment du dispatch.
     */
    public function tries(int $attempts): static
    {
        $this->maxAttempts = $attempts;

        return $this;
    }

    /**
     * Définit le timeout au moment du dispatch.
     */
    public function withTimeout(int $seconds): static
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Calcule le délai de retry pour la tentative courante.
     * Prend en compte le backoff fixe ou exponentiel.
     */
    public function retryDelay(int $attempt): int
    {
        $backoff = $this->backoff();

        if (is_array($backoff)) {
            // Backoff exponentiel : on prend l'index ou le dernier élément
            $index = max(0, $attempt - 1);

            return $backoff[$index] ?? end($backoff);
        }

        return $backoff;
    }
}