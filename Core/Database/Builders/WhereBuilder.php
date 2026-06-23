<?php

namespace Core\Database\Builders;

use Core\Database\Validators\SqlValidator;
use InvalidArgumentException;

/**
 * WhereBuilder - Constructeur de clauses WHERE
 *
 * Gère toutes les conditions WHERE:
 * - Conditions basiques (=, !=, <, >, etc.)
 * - IN, NOT IN
 * - BETWEEN, NOT BETWEEN
 * - NULL, NOT NULL
 * - Conditions imbriquées
 * - EXISTS, NOT EXISTS
 * - Comparaisons de colonnes
 * - Conditions sur dates
 * - Raw SQL
 */
class WhereBuilder
{
    private SqlValidator $validator;
    private array $wheres = [];

    public function __construct(SqlValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Ajoute une condition WHERE
     */
    public function addWhere(array $where): void
    {
        $this->wheres[] = $where;
    }

    /**
     * Récupère toutes les conditions WHERE
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * Construit une condition WHERE basique.
     *
     * Corrections apportées :
     *  1. Le bloc null lève désormais une exception si l'opérateur est incompatible
     *     (évite le retour implicite de null).
     *  2. Si $value est un tableau avec operator IN/NOT IN, on délègue à buildWhereIn()
     *     au lieu de créer un binding invalide (un seul placeholder pour un tableau).
     */
    public function buildWhere(
        string   $column,
        string   $operator,
        mixed    $value,
        string   $boolean,
        callable $placeholderGenerator
    ): array {
        $column = $this->validator->cleanInput($column);

        if (!$this->validator->isValidColumnIdentifier($column)) {
            throw new InvalidArgumentException("Invalid column name: $column");
        }

        $operator = strtoupper(trim($operator));
        $this->validator->validateOperator($operator);

        $boolean = strtoupper($boolean);
        if (!in_array($boolean, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException("Invalid boolean operator: $boolean");
        }

        // --- FIX #1 : gestion NULL exhaustive ---
        // Avant : pas de `else` → retour implicite null si l'opérateur est inconnu.
        // Maintenant : toute combinaison null + opérateur invalide lève une exception.
        if ($value === null) {
            if (in_array($operator, ['=', 'IS'], true)) {
                return [
                    'type'     => 'null',
                    'column'   => $column,
                    'operator' => 'IS NULL',
                    'boolean'  => $boolean,
                ];
            }

            if (in_array($operator, ['!=', '<>', 'IS NOT'], true)) {
                return [
                    'type'     => 'null',
                    'column'   => $column,
                    'operator' => 'IS NOT NULL',
                    'boolean'  => $boolean,
                ];
            }

            // Tout autre opérateur avec null est une erreur de programmation
            throw new InvalidArgumentException(
                "Operator '$operator' is not compatible with a NULL value. "
                . "Use '=', '!=', '<>', 'IS' or 'IS NOT' for NULL comparisons."
            );
        }

        // --- FIX #2 : tableau → déléguer à buildWhereIn() ---
        // Avant : le tableau entier était assigné à un seul placeholder PDO → erreur runtime.
        if (is_array($value)) {
            if (!in_array($operator, ['IN', 'NOT IN'], true)) {
                throw new InvalidArgumentException(
                    "Array values require IN or NOT IN operator. Use whereIn() instead."
                );
            }

            // Délégation propre : on génère autant de placeholders que de valeurs
            return $this->buildWhereIn(
                $column,
                $value,
                $boolean,
                $operator === 'NOT IN',
                $placeholderGenerator
            );
        }

        $placeholder = $placeholderGenerator($column);

        return [
            'type'        => 'basic',
            'column'      => $column,
            'operator'    => $operator,
            'placeholder' => $placeholder,
            'boolean'     => $boolean,
            'bindings'    => [$placeholder => $value],
        ];
    }

    /**
     * Construit une condition WHERE IN.
     *
     * Convention des placeholders :
     *   - Les clés des bindings sont stockées SANS le préfixe `:`.
     *   - Les placeholders dans le tableau `placeholders` sont stockés SANS `:`.
     *   - Le `:` est ajouté uniquement dans buildInWhere() au moment de la génération SQL.
     *
     * FIX #3 : uniformisation de la convention avec buildBasicWhere().
     *   Avant : `$placeholders[] = ':' . $placeholder` (avec `:`).
     *   Maintenant : `$placeholders[] = $placeholder` (sans `:`).
     *   buildInWhere() ajoute lui-même `:` devant chaque entrée.
     */
    public function buildWhereIn(
        string   $column,
        array    $values,
        string   $boolean,
        bool     $not,
        callable $placeholderGenerator
    ): array {
        $column = $this->validator->cleanInput($column);

        if (!$this->validator->isValidColumnIdentifier($column)) {
            throw new InvalidArgumentException("Invalid column name: $column");
        }

        if (empty($values)) {
            throw new InvalidArgumentException("whereIn requires at least one value");
        }

        $boolean = strtoupper($boolean);
        if (!in_array($boolean, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException("Invalid boolean operator: $boolean");
        }

        $placeholders = [];
        $bindings     = [];

        foreach ($values as $index => $value) {
            // Le générateur retourne une clé sans `:`
            $placeholder        = $placeholderGenerator($column, $index);
            $placeholders[]     = $placeholder; // sans `:` — ajouté dans buildInWhere()
            $bindings[$placeholder] = $value;
        }

        $operator = $not ? 'NOT IN' : 'IN';

        return [
            'type'         => 'in',
            'column'       => $column,
            'operator'     => $operator,
            'placeholders' => $placeholders,
            'boolean'      => $boolean,
            'bindings'     => $bindings,
        ];
    }

    /**
     * Construit une condition WHERE BETWEEN
     */
    public function buildWhereBetween(
        string   $column,
        array    $values,
        string   $boolean,
        bool     $not,
        callable $placeholderGenerator
    ): array {
        $column = $this->validator->cleanInput($column);

        if (!$this->validator->isValidColumnIdentifier($column)) {
            throw new InvalidArgumentException("Invalid column name: $column");
        }

        if (count($values) !== 2) {
            throw new InvalidArgumentException("whereBetween requires exactly 2 values");
        }

        $boolean = strtoupper($boolean);
        if (!in_array($boolean, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException("Invalid boolean operator: $boolean");
        }

        $placeholder1 = $placeholderGenerator($column, '1');
        $placeholder2 = $placeholderGenerator($column, '2');

        $operator = $not ? 'NOT BETWEEN' : 'BETWEEN';

        return [
            'type'         => 'between',
            'column'       => $column,
            'operator'     => $operator,
            'placeholder1' => $placeholder1,
            'placeholder2' => $placeholder2,
            'boolean'      => $boolean,
            'bindings'     => [
                $placeholder1 => $values[0],
                $placeholder2 => $values[1],
            ],
        ];
    }

    /**
     * Construit une condition WHERE NULL / IS NOT NULL
     */
    public function buildWhereNull(string $column, string $boolean, bool $not): array
    {
        $column = $this->validator->cleanInput($column);

        if (!$this->validator->isValidColumnIdentifier($column)) {
            throw new InvalidArgumentException("Invalid column name: $column");
        }

        $boolean = strtoupper($boolean);
        if (!in_array($boolean, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException("Invalid boolean operator: $boolean");
        }

        $operator = $not ? 'IS NOT NULL' : 'IS NULL';

        return [
            'type'     => 'null',
            'column'   => $column,
            'operator' => $operator,
            'boolean'  => $boolean,
        ];
    }

    /**
     * Construit la clause WHERE complète.
     *
     * Note sur le boolean de la première condition : par convention (identique à
     * Laravel/Eloquent), le boolean de l'entrée d'index 0 est toujours ignoré.
     * Le premier terme ne peut pas être précédé d'un AND/OR. C'est documenté ici
     * explicitement pour éviter toute confusion lors d'une future refactorisation.
     */
    public function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = [];

        foreach ($this->wheres as $index => $where) {
            // Le boolean du premier where est volontairement ignoré (pas de AND/OR avant)
            $prefix = $index === 0 ? '' : " {$where['boolean']} ";

            switch ($where['type']) {
                case 'basic':
                    $sql[] = $prefix . $this->buildBasicWhere($where);
                    break;

                case 'in':
                    $sql[] = $prefix . $this->buildInWhere($where);
                    break;

                case 'between':
                    $sql[] = $prefix . $this->buildBetweenWhere($where);
                    break;

                case 'null':
                    $sql[] = $prefix . "{$where['column']} {$where['operator']}";
                    break;

                case 'nested':
                    $nestedSql = $where['query']->buildWhereClause();
                    if (!empty($nestedSql)) {
                        $sql[] = $prefix . "($nestedSql)";
                    }
                    break;

                case 'exists':
                    $sql[] = $prefix . "{$where['operator']} ({$where['query']})";
                    break;

                case 'column':
                    $sql[] = $prefix . "{$where['first']} {$where['operator']} {$where['second']}";
                    break;

                case 'date':
                    $sql[] = $prefix . "{$where['function']}({$where['column']}) {$where['operator']} :{$where['placeholder']}";
                    break;

                case 'raw':
                    $sql[] = $prefix . $where['sql'];
                    break;

                default:
                    throw new InvalidArgumentException("Unknown where type: {$where['type']}");
            }
        }

        return implode('', $sql);
    }

    /**
     * Génère le fragment SQL d'une condition basique.
     * Convention : le `:` est ajouté ici (placeholder stocké sans `:`).
     */
    private function buildBasicWhere(array $where): string
    {
        return "{$where['column']} {$where['operator']} :{$where['placeholder']}";
    }

    /**
     * Génère le fragment SQL d'une condition IN / NOT IN.
     * FIX #3 : les placeholders sont désormais stockés sans `:` → on l'ajoute ici.
     */
    private function buildInWhere(array $where): string
    {
        // Ajout du `:` devant chaque placeholder (convention uniforme avec buildBasicWhere)
        $placeholders = implode(', ', array_map(
            static fn(string $p) => ':' . $p,
            $where['placeholders']
        ));

        return "{$where['column']} {$where['operator']} ($placeholders)";
    }

    /**
     * Génère le fragment SQL d'une condition BETWEEN / NOT BETWEEN.
     * Convention : le `:` est ajouté ici.
     */
    private function buildBetweenWhere(array $where): string
    {
        return "{$where['column']} {$where['operator']} :{$where['placeholder1']} AND :{$where['placeholder2']}";
    }

    /**
     * Réinitialise toutes les conditions WHERE
     */
    public function reset(): void
    {
        $this->wheres = [];
    }

    /**
     * Clone — le tableau $wheres est copié par valeur automatiquement.
     */
    public function __clone()
    {
        // Rien à faire : les tableaux PHP sont copiés par valeur lors du clone.
    }
}