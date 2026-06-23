<?php

namespace Core\Database\Validators;

use InvalidArgumentException;

/**
 * SqlValidator - Validation et nettoyage des entrées SQL
 * 
 * Prévient les injections SQL en validant strictement:
 * - Les noms de tables
 * - Les noms de colonnes
 * - Les opérateurs
 * - Les identifiants SQL
 */
class SqlValidator
{
    // Opérateurs SQL autorisés
    private const ALLOWED_OPERATORS = [
        '=', '!=', '<>', '<', '>', '<=', '>=',
        'LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE',
        'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
        'IS', 'IS NOT', 'IS NULL', 'IS NOT NULL',
        'REGEXP', 'NOT REGEXP', 'RLIKE', 'SOUNDS LIKE',
        'EXISTS', 'NOT EXISTS'
    ];

    // Fonctions SQL autorisées dans les colonnes
    private const ALLOWED_FUNCTIONS = [
        'COUNT', 'SUM', 'AVG', 'MIN', 'MAX',
        'CONCAT', 'COALESCE', 'NULLIF', 'IF', 'IFNULL',
        'UPPER', 'LOWER', 'TRIM', 'LTRIM', 'RTRIM',
        'LENGTH', 'SUBSTRING', 'REPLACE', 'REVERSE',
        'DATE', 'TIME', 'YEAR', 'MONTH', 'DAY',
        'HOUR', 'MINUTE', 'SECOND', 'NOW', 'CURDATE', 'CURTIME',
        'DATE_FORMAT', 'STR_TO_DATE', 'UNIX_TIMESTAMP',
        'ROUND', 'CEIL', 'FLOOR', 'ABS', 'POW', 'SQRT',
        'CAST', 'CONVERT', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END',
        'DISTINCT', 'GROUP_CONCAT', 'JSON_EXTRACT', 'JSON_UNQUOTE'
    ];

    /**
     * Nettoie l'entrée en supprimant les commentaires SQL et espaces superflus
     */
    public function cleanInput(string $input): string
    {
        // Supprime les commentaires SQL de type /* ... */
        $input = preg_replace('/\/\*.*?\*\//s', '', $input);
        
        // Supprime les commentaires de type -- ...
        $input = preg_replace('/--[^\n]*/', '', $input);
        
        // Supprime les commentaires de type # ...
        $input = preg_replace('/#[^\n]*/', '', $input);
        
        // Normalise les espaces
        $input = preg_replace('/\s+/', ' ', $input);
        
        return trim($input);
    }

    /**
     * Valide un identifiant de table
     */
    public function isValidTableIdentifier(string $identifier): bool
    {
        $identifier = trim($identifier);
        
        // Pattern pour table simple: table ou `table`
        $simplePattern = '/^`?[a-zA-Z_][a-zA-Z0-9_]*`?$/';
        
        // Pattern pour table avec alias: table alias ou table AS alias
        $aliasPattern = '/^`?[a-zA-Z_][a-zA-Z0-9_]*`?\s+(?:AS\s+)?`?[a-zA-Z_][a-zA-Z0-9_]*`?$/i';
        
        // Pattern pour table qualifiée: schema.table
        $qualifiedPattern = '/^`?[a-zA-Z_][a-zA-Z0-9_]*`?\.`?[a-zA-Z_][a-zA-Z0-9_]*`?$/';
        
        // Pattern pour table qualifiée avec alias
        $qualifiedAliasPattern = '/^`?[a-zA-Z_][a-zA-Z0-9_]*`?\.`?[a-zA-Z_][a-zA-Z0-9_]*`?\s+(?:AS\s+)?`?[a-zA-Z_][a-zA-Z0-9_]*`?$/i';
        
        return preg_match($simplePattern, $identifier) === 1
            || preg_match($aliasPattern, $identifier) === 1
            || preg_match($qualifiedPattern, $identifier) === 1
            || preg_match($qualifiedAliasPattern, $identifier) === 1;
    }

    /**
     * Valide un identifiant de colonne
     */
    public function isValidColumnIdentifier(string $identifier): bool
    {
        $identifier = trim($identifier);
        
        // Si c'est juste *
        if ($identifier === '*') {
            return true;
        }

        // Pattern pour colonne simple: name, table.name, `name`, `table`.`name`
        $simplePattern = '/^(?:`?[a-zA-Z_][a-zA-Z0-9_]*`?\.)?`?[a-zA-Z_*][a-zA-Z0-9_*]*`?$/';
        
        // Pattern pour colonne avec alias: name AS alias, name alias
        $aliasPattern = '/^(?:`?[a-zA-Z_][a-zA-Z0-9_]*`?\.)?`?[a-zA-Z_*][a-zA-Z0-9_*]*`?\s+(?:AS\s+)?`?[a-zA-Z_][a-zA-Z0-9_]*`?$/i';
        
        // Pattern pour fonctions SQL
        if ($this->isValidFunction($identifier)) {
            return true;
        }
        
        // Pattern pour expressions CASE
        if ($this->isValidCaseExpression($identifier)) {
            return true;
        }
        
        // Pattern pour opérations arithmétiques: column + column, column * 2
        $operationPattern = '/^(?:`?[a-zA-Z_][a-zA-Z0-9_]*`?\.)?`?[a-zA-Z_][a-zA-Z0-9_]*`?\s*[\+\-\*\/\%]\s*.+$/';
        
        return preg_match($simplePattern, $identifier) === 1
            || preg_match($aliasPattern, $identifier) === 1
            || preg_match($operationPattern, $identifier) === 1;
    }

    /**
     * Valide une fonction SQL
     */
    private function isValidFunction(string $expression): bool
    {
        // Extrait le nom de la fonction
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/i', $expression, $matches)) {
            $functionName = strtoupper($matches[1]);
            
            // Vérifie si la fonction est autorisée
            if (in_array($functionName, self::ALLOWED_FUNCTIONS, true)) {
                // Vérifie que les parenthèses sont équilibrées
                return $this->hasBalancedParentheses($expression);
            }
        }
        
        return false;
    }

    /**
     * Valide une expression CASE
     */
    private function isValidCaseExpression(string $expression): bool
    {
        $upper = strtoupper($expression);
        
        if (strpos($upper, 'CASE') === 0) {
            // Doit contenir WHEN et END
            return strpos($upper, 'WHEN') !== false && strpos($upper, 'END') !== false;
        }
        
        return false;
    }

    /**
     * Vérifie que les parenthèses sont équilibrées
     */
    private function hasBalancedParentheses(string $expression): bool
    {
        $count = 0;
        $length = strlen($expression);
        
        for ($i = 0; $i < $length; $i++) {
            if ($expression[$i] === '(') {
                $count++;
            } elseif ($expression[$i] === ')') {
                $count--;
                if ($count < 0) {
                    return false;
                }
            }
        }
        
        return $count === 0;
    }

    /**
     * Valide un opérateur SQL
     */
    public function validateOperator(string $operator): void
    {
        $operator = strtoupper(trim($operator));
        
        if (!in_array($operator, self::ALLOWED_OPERATORS, true)) {
            throw new InvalidArgumentException("Invalid SQL operator: $operator");
        }
    }

    /**
     * Vérifie si un opérateur est valide (sans exception)
     */
    public function isValidOperator(string $operator): bool
    {
        $operator = strtoupper(trim($operator));
        return in_array($operator, self::ALLOWED_OPERATORS, true);
    }

    /**
     * Échappe un identifiant SQL (table ou colonne)
     */
    public function escapeIdentifier(string $identifier): string
    {
        // Retire les backticks existants
        $identifier = str_replace('`', '', $identifier);
        
        // Ajoute les backticks
        if (strpos($identifier, '.') !== false) {
            // Table qualifiée
            $parts = explode('.', $identifier);
            return '`' . implode('`.`', $parts) . '`';
        }
        
        return '`' . $identifier . '`';
    }

    /**
     * Valide un nom de fonction d'agrégation
     */
    public function isValidAggregateFunction(string $function): bool
    {
        $function = strtoupper(trim($function));
        
        $aggregateFunctions = ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'GROUP_CONCAT', 'STD', 'STDDEV', 'VARIANCE'];
        
        return in_array($function, $aggregateFunctions, true);
    }

    /**
     * Valide un type de JOIN
     */
    public function isValidJoinType(string $type): bool
    {
        $type = strtoupper(trim($type));
        
        $validTypes = ['INNER', 'LEFT', 'RIGHT', 'FULL', 'CROSS', 'LEFT OUTER', 'RIGHT OUTER', 'FULL OUTER'];
        
        return in_array($type, $validTypes, true);
    }

    /**
     * Valide une direction de tri
     */
    public function isValidOrderDirection(string $direction): bool
    {
        $direction = strtoupper(trim($direction));
        return in_array($direction, ['ASC', 'DESC'], true);
    }

    /**
     * Détecte les tentatives d'injection SQL courantes
     */
    public function detectInjection(string $input): bool
    {
        $input = strtolower($input);
        
        // Patterns d'injection courants
        $dangerousPatterns = [
            '/(\bor\b|\band\b)\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+[\'"]?/i', // OR 1=1, AND 1=1
            '/union\s+select/i',                                             // UNION SELECT
            '/;\s*drop\s+/i',                                                // ; DROP
            '/;\s*delete\s+/i',                                              // ; DELETE
            '/;\s*update\s+/i',                                              // ; UPDATE
            '/;\s*insert\s+/i',                                              // ; INSERT
            '/exec\s*\(/i',                                                  // exec(
            '/execute\s*\(/i',                                               // execute(
            '/script\s*>/i',                                                 // XSS
            '/<\s*iframe/i',                                                 // XSS iframe
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Sanitize une valeur (retire les caractères dangereux)
     */
    public function sanitizeValue($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        // Pour les chaînes, on utilise addslashes comme protection de base
        // Note: Le vrai échappement devrait se faire via PDO prepare/execute
        return addslashes((string) $value);
    }

    /**
     * Valide un alias
     */
    public function isValidAlias(string $alias): bool
    {
        // Un alias doit être un identifiant simple
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias) === 1;
    }

    /**
     * Extrait le nom de colonne d'une expression
     */
    public function extractColumnName(string $expression): string
    {
        // Enlève les backticks
        $expression = str_replace('`', '', $expression);
        
        // Si c'est une fonction, extrait le nom entre AS
        if (preg_match('/\s+AS\s+(\w+)$/i', $expression, $matches)) {
            return $matches[1];
        }
        
        // Si c'est table.column, prend juste column
        if (strpos($expression, '.') !== false) {
            $parts = explode('.', $expression);
            return end($parts);
        }
        
        return $expression;
    }

    /**
     * Valide que la valeur n'est pas un tableau dans un contexte non-IN
     */
    public function validateNotArray($value, string $context = 'value'): void
    {
        if (is_array($value)) {
            throw new InvalidArgumentException(
                "Array values are not allowed in this context ($context). Use whereIn() for array values."
            );
        }
    }

    /**
     * Valide un nom de séquence (pour PostgreSQL)
     */
    public function isValidSequenceName(string $sequence): bool
    {
        // Similaire à un nom de table
        return $this->isValidTableIdentifier($sequence);
    }
}
