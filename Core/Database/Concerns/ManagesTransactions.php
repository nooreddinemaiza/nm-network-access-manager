<?php

namespace Core\Database\Concerns;

use RuntimeException;
use Throwable;

/**
 * ManagesTransactions - Gestion des transactions
 * 
 * Fournit des méthodes pour gérer les transactions de manière sûre
 */
trait ManagesTransactions
{
    /**
     * Exécute un callback dans une transaction
     */
    public function transaction(callable $callback, int $attempts = 1)
    {
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            try {
                $this->db->beginTransaction();
                
                $result = $callback($this);
                
                $this->db->commitTransaction();
                
                return $result;
            } catch (Throwable $e) {
                $this->db->rollbackTransaction();
                
                // Si c'est la dernière tentative, relancer l'exception
                if ($currentAttempt >= $attempts) {
                    throw $e;
                }
                
                // Attendre un peu avant de réessayer (backoff exponentiel)
                if ($currentAttempt < $attempts) {
                    usleep(100000 * $currentAttempt); // 0.1s, 0.2s, 0.3s...
                }
            }
        }
    }

    /**
     * Transaction avec gestion automatique des erreurs
     */
    public function transactionOrThrow(callable $callback)
    {
        return $this->transaction($callback, 1);
    }

    /**
     * Démarre une transaction manuellement
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Commit une transaction
     */
    public function commit(): void
    {
        $this->db->commitTransaction();
    }

    /**
     * Rollback une transaction
     */
    public function rollback(): void
    {
        $this->db->rollbackTransaction();
    }

    /**
     * Exécute plusieurs requêtes dans une transaction
     */
    public function bulkTransaction(array $queries): bool
    {
        return $this->transaction(function() use ($queries) {
            foreach ($queries as $query) {
                if (is_callable($query)) {
                    $query($this);
                }
            }
            return true;
        });
    }

    /**
     * Vérifie si une transaction est active
     */
    public function inTransaction(): bool
    {
        return $this->db->getPdo()->inTransaction();
    }

    /**
     * Savepoint (pour transactions imbriquées)
     */
    public function savepoint(string $name): void
    {
        $this->db->query("SAVEPOINT $name");
    }

    /**
     * Rollback to savepoint
     */
    public function rollbackToSavepoint(string $name): void
    {
        $this->db->query("ROLLBACK TO SAVEPOINT $name");
    }

    /**
     * Release savepoint
     */
    public function releaseSavepoint(string $name): void
    {
        $this->db->query("RELEASE SAVEPOINT $name");
    }
}
