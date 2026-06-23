<?php

namespace Core\Database\Concerns;

/**
 * BuildsQueries - Méthodes utilitaires pour construire des requêtes
 * 
 * Contient des méthodes helper pour simplifier la construction de requêtes
 */
trait BuildsQueries
{
    /**
     * Applique un callback si la condition est vraie
     */
    public function when($value, callable $callback, ?callable $default = null): self
    {
        if ($value) {
            return $callback($this, $value) ?? $this;
        } elseif ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
    }

    /**
     * Applique un callback si la condition est fausse
     */
    public function unless($value, callable $callback, ?callable $default = null): self
    {
        if (!$value) {
            return $callback($this, $value) ?? $this;
        } elseif ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
    }

    /**
     * Tap - Exécute un callback sur le builder et retourne le builder
     */
    public function tap(callable $callback): self
    {
        $callback($this);
        return $this;
    }

    /**
     * Pipe - Passe le builder dans un callback et retourne le résultat
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * Applique plusieurs wheres depuis un tableau
     */
    public function whereAll(array $conditions): self
    {
        foreach ($conditions as $column => $value) {
            if (is_numeric($column)) {
                // C'est un tableau de conditions [column, operator, value]
                if (is_array($value)) {
                    $this->where(...$value);
                }
            } else {
                // C'est un tableau associatif [column => value]
                $this->where($column, '=', $value);
            }
        }

        return $this;
    }

    /**
     * WHERE avec ANY (OR entre plusieurs conditions)
     */
    public function whereAny(array $columns, $operator = '=', $value = null): self
    {
        return $this->where(function($query) use ($columns, $operator, $value) {
            foreach ($columns as $column) {
                $query->orWhere($column, $operator, $value);
            }
        });
    }

    /**
     * Applique un scope (méthode réutilisable)
     */
    public function scope(callable $scope, ...$parameters): self
    {
        return $scope($this, ...$parameters) ?? $this;
    }

    /**
     * Macro - Permet d'enregistrer des méthodes personnalisées
     * Note: Ceci nécessiterait un système de macros global
     */
    public function macro(string $name, callable $callback): void
    {
        // Cette méthode nécessiterait un registre global de macros
        // Pour l'instant, c'est un placeholder
    }
}
