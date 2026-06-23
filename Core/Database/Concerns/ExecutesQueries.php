<?php

namespace Core\Database\Concerns;

use RuntimeException;

/**
 * ExecutesQueries - Méthodes pour exécuter des requêtes spéciales
 * 
 * Contient des méthodes d'exécution avancées et des helpers
 */
trait ExecutesQueries
{
    /**
     * Exécute une requête et retourne un seul enregistrement
     * Lance une exception si pas trouvé ou si plusieurs résultats
     */
    public function sole(array $columns = ['*']): array
    {
        if (!empty($columns)) {
            $this->select(...$columns);
        }

        $results = $this->limit(2)->get();

        if (empty($results)) {
            throw new RuntimeException("No results found");
        }

        if (count($results) > 1) {
            throw new RuntimeException("Multiple results found, expected only one");
        }

        return $results[0];
    }

    /**
     * Trouve plusieurs enregistrements par IDs
     */
    public function findMany(array $ids, string $column = 'id'): array
    {
        return $this->whereIn($column, $ids)->get();
    }

    /**
     * Récupère ou crée un enregistrement
     */
    public function firstOrCreate(array $attributes, array $values = []): array
    {
        $record = (clone $this)->where($attributes)->first();

        if ($record !== null) {
            return $record;
        }

        // Créer
        $data = array_merge($attributes, $values);
        $this->insert($data);

        // Récupérer l'enregistrement créé
        return (clone $this)->where($attributes)->firstOrFail();
    }

    /**
     * Récupère le premier enregistrement ou retourne une valeur par défaut
     */
    public function firstOr(callable $callback = null, array $columns = ['*'])
    {
        if (!empty($columns)) {
            $this->select(...$columns);
        }

        $result = $this->first();

        if ($result !== null) {
            return $result;
        }

        return $callback ? $callback() : null;
    }

    /**
     * Vérifie si exactement N résultats existent
     */
    public function count(): int
    {
        return $this->aggregateBuilder->count($this);
    }

    /**
     * Lance une exception si aucun résultat
     */
    public function existsOr(callable $callback): bool
    {
        if ($this->exists()) {
            return true;
        }

        $callback();
        return false;
    }

    /**
     * Crée ou met à jour un enregistrement
     */
    public function createOrUpdate(array $attributes, array $values = []): bool
    {
        return $this->updateOrInsert($attributes, $values);
    }

    /**
     * Touch (met à jour updated_at)
     */
    public function touch(string $column = 'updated_at'): int
    {
        return $this->update([
            $column => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Retourne true si le count est supérieur à N
     */
    public function countIsGreaterThan(int $threshold): bool
    {
        return $this->count() > $threshold;
    }

    /**
     * Exécute un callback pour chaque résultat avec chunk
     */
    public function chunkById(int $size, callable $callback, string $column = 'id'): bool
    {
        $lastId = null;

        do {
            $query = clone $this;

            if ($lastId !== null) {
                $query->where($column, '>', $lastId);
            }

            $results = $query->orderBy($column)->limit($size)->get();

            if (empty($results)) {
                break;
            }

            if ($callback($results) === false) {
                return false;
            }

            $lastId = end($results)[$column];

        } while (count($results) === $size);

        return true;
    }

    /**
     * Lazy loading avec cursor
     */
    public function lazy(int $chunkSize = 1000): \Generator
    {
        foreach ($this->chunk($chunkSize, function($results) {
            foreach ($results as $result) {
                yield $result;
            }
        }) as $result) {
            yield $result;
        }
    }

    /**
     * LazyById - Plus efficace pour grandes tables
     */
    public function lazyById(int $chunkSize = 1000, string $column = 'id'): \Generator
    {
        $this->chunkById($chunkSize, function($results) {
            foreach ($results as $result) {
                yield $result;
            }
        }, $column);
    }
}
