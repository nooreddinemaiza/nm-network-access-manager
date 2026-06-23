<?php

namespace Core\Database\Builders;

use Core\Database\Validators\SqlValidator;
use InvalidArgumentException;

/**
 * JoinBuilder - Constructeur de clauses JOIN
 * 
 * Supporte:
 * - INNER JOIN
 * - LEFT JOIN
 * - RIGHT JOIN
 * - FULL JOIN
 * - CROSS JOIN
 * - Joins avec conditions multiples
 * - Raw joins
 */
class JoinBuilder
{
    private SqlValidator $validator;
    private array $joins = [];

    public function __construct(SqlValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Ajoute un JOIN
     */
    public function addJoin(array $join): void
    {
        $this->joins[] = $join;
    }

    /**
     * Récupère tous les JOINs
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * Construit un JOIN simple
     */
    public function buildJoin(string $table, string $first, ?string $operator, ?string $second, string $type): array
    {
        $table = $this->validator->cleanInput($table);
        $first = $this->validator->cleanInput($first);
        
        if (!$this->validator->isValidTableIdentifier($table)) {
            throw new InvalidArgumentException("Invalid table name: $table");
        }

        if (!$this->validator->isValidColumnIdentifier($first)) {
            throw new InvalidArgumentException("Invalid first column: $first");
        }

        $type = strtoupper($type);
        if (!$this->validator->isValidJoinType($type)) {
            throw new InvalidArgumentException("Invalid join type: $type");
        }

        // Si pas d'opérateur fourni, on suppose '='
        if ($operator === null) {
            throw new InvalidArgumentException("JOIN requires an operator");
        }

        if ($second === null) {
            throw new InvalidArgumentException("JOIN requires a second column");
        }

        $second = $this->validator->cleanInput($second);
        if (!$this->validator->isValidColumnIdentifier($second)) {
            throw new InvalidArgumentException("Invalid second column: $second");
        }

        $operator = strtoupper(trim($operator));
        $this->validator->validateOperator($operator);

        return [
            'type' => 'simple',
            'join_type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
    }

    /**
     * Construit un CROSS JOIN
     */
    public function buildCrossJoin(string $table): array
    {
        $table = $this->validator->cleanInput($table);
        
        if (!$this->validator->isValidTableIdentifier($table)) {
            throw new InvalidArgumentException("Invalid table name: $table");
        }

        return [
            'type' => 'cross',
            'join_type' => 'CROSS',
            'table' => $table
        ];
    }

    /**
     * Construit la clause JOIN complète
     */
    public function buildJoinClause(): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = [];

        foreach ($this->joins as $join) {
            switch ($join['type']) {
                case 'simple':
                    $sql[] = $this->buildSimpleJoin($join);
                    break;

                case 'cross':
                    $sql[] = $this->buildCrossJoinClause($join);
                    break;

                case 'complex':
                    $sql[] = $this->buildComplexJoin($join);
                    break;

                case 'raw':
                    $sql[] = $join['sql'];
                    break;

                default:
                    throw new InvalidArgumentException("Unknown join type: {$join['type']}");
            }
        }

        return ' ' . implode(' ', $sql);
    }

    /**
     * Construit un JOIN simple
     */
    private function buildSimpleJoin(array $join): string
    {
        return "{$join['join_type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
    }

    /**
     * Construit un CROSS JOIN
     */
    private function buildCrossJoinClause(array $join): string
    {
        return "CROSS JOIN {$join['table']}";
    }

    /**
     * Construit un JOIN complexe (avec plusieurs conditions)
     */
    private function buildComplexJoin(array $join): string
    {
        $clauses = [];
        
        foreach ($join['clauses'] as $index => $clause) {
            $boolean = $index === 0 ? '' : " {$clause['boolean']} ";
            
            if ($clause['type'] === 'on') {
                $clauses[] = $boolean . "{$clause['first']} {$clause['operator']} {$clause['second']}";
            } elseif ($clause['type'] === 'where') {
                $clauses[] = $boolean . "{$clause['column']} {$clause['operator']} :{$clause['placeholder']}";
            }
        }

        $conditions = implode('', $clauses);
        
        return "{$join['join_type']} JOIN {$join['table']} ON $conditions";
    }

    /**
     * Réinitialise les joins
     */
    public function reset(): void
    {
        $this->joins = [];
    }

    /**
     * Clone
     */
    public function __clone()
    {
        // Les joins sont clonés automatiquement (tableau)
    }
}
