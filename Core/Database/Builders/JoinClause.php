<?php

namespace Core\Database\Builders;

use Core\Database\Validators\SqlValidator;
use InvalidArgumentException;

/**
 * JoinClause - Gère les conditions de JOIN complexes
 * 
 * Permet de construire des JOINs avec plusieurs conditions:
 * - ON conditions
 * - WHERE conditions dans le JOIN
 * - AND/OR conditions
 */
class JoinClause
{
    private SqlValidator $validator;
    private string $table;
    private string $type;
    private array $clauses = [];

    public function __construct(SqlValidator $validator, string $table, string $type)
    {
        $this->validator = $validator;
        $this->table = $table;
        $this->type = $type;
    }

    /**
     * Ajoute une condition ON
     */
    public function on(string $first, string $operator, string $second, string $boolean = 'AND'): self
    {
        $first = $this->validator->cleanInput($first);
        $second = $this->validator->cleanInput($second);

        if (!$this->validator->isValidColumnIdentifier($first)) {
            throw new InvalidArgumentException("Invalid first column: $first");
        }

        if (!$this->validator->isValidColumnIdentifier($second)) {
            throw new InvalidArgumentException("Invalid second column: $second");
        }

        $operator = strtoupper(trim($operator));
        $this->validator->validateOperator($operator);

        $boolean = strtoupper($boolean);
        if (!in_array($boolean, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException("Invalid boolean operator: $boolean");
        }

        $this->clauses[] = [
            'type' => 'on',
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'boolean' => $boolean
        ];

        return $this;
    }

    /**
     * Ajoute une condition OR ON
     */
    public function orOn(string $first, string $operator, string $second): self
    {
        return $this->on($first, $operator, $second, 'OR');
    }

    /**
     * Ajoute une condition WHERE dans le JOIN
     */
    public function where(string $column, string $operator, $value, string $boolean = 'AND'): self
    {
        $column = $this->validator->cleanInput($column);

        if (!$this->validator->isValidColumnIdentifier($column)) {
            throw new InvalidArgumentException("Invalid column: $column");
        }

        $operator = strtoupper(trim($operator));
        $this->validator->validateOperator($operator);

        $boolean = strtoupper($boolean);
        if (!in_array($boolean, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException("Invalid boolean operator: $boolean");
        }

        // Générer un placeholder unique
        static $counter = 0;
        $counter++;
        $placeholder = 'join_where_' . $counter;

        $this->clauses[] = [
            'type' => 'where',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'placeholder' => $placeholder,
            'boolean' => $boolean
        ];

        return $this;
    }

    /**
     * Ajoute une condition OR WHERE dans le JOIN
     */
    public function orWhere(string $column, string $operator, $value): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Récupère les clauses
     */
    public function getClauses(): array
    {
        return $this->clauses;
    }

    /**
     * Récupère la table
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Récupère le type
     */
    public function getType(): string
    {
        return $this->type;
    }
    /**
     * Ajoute une condition WHERE RAW dans le JOIN
     */
    public function whereRaw(string $sql): self
    {
        $this->clauses[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Ajoute une condition OR WHERE RAW dans le JOIN
     */
    public function orWhereRaw(string $sql): self
    {
        $this->clauses[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => 'OR'
        ];

        return $this;
    }
}
