<?php

namespace Core\Database\Builders;

use Core\Database\Validators\SqlValidator;
use Core\Database\QueryBuilder;
use InvalidArgumentException;

/**
 * AggregateBuilder - Gère les fonctions d'agrégation
 * 
 * Supporte:
 * - COUNT
 * - SUM
 * - AVG
 * - MIN
 * - MAX
 * - Agrégats personnalisés
 */
class AggregateBuilder
{
    private SqlValidator $validator;

    public function __construct(SqlValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * COUNT
     */
    public function count(QueryBuilder $query, string $column = '*'): int
    {
        $column = $this->validator->cleanInput($column);
        
        if ($column !== '*' && !$this->validator->isValidColumnIdentifier($column)) {
            throw new InvalidArgumentException("Invalid column name for count: $column");
        }

        return $this->executeAggregate($query, 'COUNT', $column);
    }

    /**
     * MIN
     */
    public function min(QueryBuilder $query, string $column)
    {
        $column = $this->validateAggregateColumn($column);
        return $this->executeAggregate($query, 'MIN', $column);
    }

    /**
     * MAX
     */
    public function max(QueryBuilder $query, string $column)
    {
        $column = $this->validateAggregateColumn($column);
        return $this->executeAggregate($query, 'MAX', $column);
    }

    /**
     * AVG
     */
    public function avg(QueryBuilder $query, string $column)
    {
        $column = $this->validateAggregateColumn($column);
        return $this->executeAggregate($query, 'AVG', $column);
    }

    /**
     * SUM
     */
    public function sum(QueryBuilder $query, string $column)
    {
        $column = $this->validateAggregateColumn($column);
        $result = $this->executeAggregate($query, 'SUM', $column);
        return $result ?? 0;
    }

    /**
     * Agrégat personnalisé
     */
    public function aggregate(QueryBuilder $query, string $function, string $column)
    {
        $function = strtoupper(trim($function));
        
        if (!$this->validator->isValidAggregateFunction($function)) {
            throw new InvalidArgumentException("Invalid aggregate function: $function");
        }

        $column = $this->validateAggregateColumn($column);
        
        return $this->executeAggregate($query, $function, $column);
    }

    /**
     * Valide une colonne pour agrégat
     */
    private function validateAggregateColumn(string $column): string
    {
        $column = $this->validator->cleanInput($column);
        
        if (!$this->validator->isValidColumnIdentifier($column)) {
            throw new InvalidArgumentException("Invalid column name: $column");
        }

        return $column;
    }

    /**
     * Exécute une fonction d'agrégation
     */
    private function executeAggregate(QueryBuilder $query, string $function, string $column)
    {
        // Sauvegarder l'état actuel
        $originalColumns = $query->getColumns();
        $originalLimit = $query->getLimit();
        $originalOffset = $query->getOffset();

        // Configurer pour l'agrégat
        $query->setColumns(["$function($column) as aggregate"]);
        $query->setLimit(null);
        $query->setOffset(null);

        try {
            $result = $query->first();
            
            if ($function === 'COUNT') {
                return (int)($result['aggregate'] ?? 0);
            }
            
            return $result['aggregate'] ?? null;
        } finally {
            // Restaurer l'état
            $query->setColumns($originalColumns);
            $query->setLimit($originalLimit);
            $query->setOffset($originalOffset);
        }
    }
}
