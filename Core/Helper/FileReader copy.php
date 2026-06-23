<?php

namespace Core;

use InvalidArgumentException;

/**
 * LineFilter — Constructeur de filtres de lignes chaînable et immutable
 *
 * Chaque méthode retourne une NOUVELLE instance : zéro effet de bord,
 * possibilité de brancher plusieurs variantes depuis un filtre de base.
 *
 * Toutes les conditions s'enchaînent en AND entre elles.
 * Le mode OR / AND sur un tableau se contrôle règle par règle via $and.
 *
 * ┌─────────────────────────────────────────────────────────────────────┐
 * │  RÉFÉRENCE RAPIDE                                                   │
 * ├───────────────────────────────────────┬─────────────────────────────┤
 * │ contains('x')                         │ contient "x"               │
 * │ contains(['x','y'])                   │ contient "x" OU "y" (OR)   │
 * │ contains(['x','y'], and: true)        │ contient "x" ET "y" (AND)  │
 * │ excludes('x')                         │ ne contient pas "x"        │
 * │ excludes(['x','y'])                   │ ni "x" ni "y" (OR)         │
 * │ excludes(['x','y'], and: true)        │ pas les deux ensemble      │
 * │ startsWith(['a','b'])                 │ commence par "a" ou "b"    │
 * │ endsWith(['.log','.txt'])             │ finit par l'un des deux    │
 * │ matches('/regex/i')                   │ correspond à la regex      │
 * │ notMatches('/regex/')                 │ ne correspond pas          │
 * │ where(fn($l) => …)                    │ prédicat libre inclusif    │
 * │ whereNot(fn($l) => …)                 │ prédicat libre exclusif    │
 * │ notEmpty()                            │ non vide après trim        │
 * │ minLength(n) / maxLength(n)           │ contrainte de longueur     │
 * └───────────────────────────────────────┴─────────────────────────────┘
 *
 * Exemples
 * --------
 *
 * // ERROR ou CRITICAL, mais pas dans les envs de test (insensible casse)
 * $filter = LineFilter::new()
 *     ->contains(['ERROR', 'CRITICAL'])
 *     ->excludes(['staging', 'test'], caseSensitive: false);
 *
 * // Doit contenir "payment" ET "failed" (AND explicite)
 * $filter = LineFilter::new()
 *     ->contains(['payment', 'failed'], and: true);
 *
 * // Réutilisation sans effet de bord
 * $base    = LineFilter::new()->contains('ERROR')->excludes('deprecated');
 * $strict  = $base->minLength(50);       // variante plus restrictive
 * $verbose = $base->where(fn($l) => str_contains($l, 'stack'));
 */
final class LineFilter
{
    /**
     * @var array<array{
     *   type:  string,
     *   value: mixed,
     *   mode:  'include'|'exclude',
     *   case:  bool,
     *   match: 'or'|'and'
     * }>
     */
    private array $rules;

    private function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    // =========================================================================
    // Point d'entrée
    // =========================================================================

    /** Crée un nouveau filtre vide. */
    public static function new(): self
    {
        return new self();
    }

    // =========================================================================
    // Règles inclusives — la ligne DOIT satisfaire la condition
    // =========================================================================

    /**
     * La ligne doit contenir la valeur ou satisfaire le tableau selon $and.
     *
     * $and = false (défaut) : AU MOINS UN élément présent (OR)
     * $and = true           : TOUS les éléments présents  (AND)
     *
     * @param string|string[] $needle
     */
    public function contains(
        string|array $needle,
        bool $caseSensitive = true,
        bool $and = false
    ): self {
        return $this->addRule('contains', $needle, 'include', $caseSensitive, $and ? 'and' : 'or');
    }

    /**
     * La ligne doit commencer par la valeur ou satisfaire le tableau selon $and.
     *
     * @param string|string[] $prefix
     */
    public function startsWith(
        string|array $prefix,
        bool $caseSensitive = true,
        bool $and = false
    ): self {
        return $this->addRule('starts_with', $prefix, 'include', $caseSensitive, $and ? 'and' : 'or');
    }

    /**
     * La ligne doit se terminer par la valeur ou satisfaire le tableau selon $and.
     *
     * @param string|string[] $suffix
     */
    public function endsWith(
        string|array $suffix,
        bool $caseSensitive = true,
        bool $and = false
    ): self {
        return $this->addRule('ends_with', $suffix, 'include', $caseSensitive, $and ? 'and' : 'or');
    }

    /**
     * La ligne doit correspondre à l'expression régulière.
     * Le pattern doit inclure ses délimiteurs : '/pattern/flags'
     *
     * @example matches('/\[ERROR\]/i')
     * @example matches('/^\d{4}-\d{2}-\d{2}/')
     */
    public function matches(string $pattern): self
    {
        self::assertValidRegex($pattern);
        return $this->addRule('regex', $pattern, 'include', true, 'or');
    }

    /**
     * La ligne doit satisfaire le prédicat callable.
     *
     * @param callable(string): bool $predicate
     */
    public function where(callable $predicate): self
    {
        return $this->addRule('callable', $predicate, 'include', true, 'or');
    }

    /** La ligne ne doit pas être vide (après trim). */
    public function notEmpty(): self
    {
        return $this->addRule('not_empty', null, 'include', true, 'or');
    }

    /** La ligne doit avoir au moins $min caractères (mb_strlen). */
    public function minLength(int $min): self
    {
        if ($min < 0) {
            throw new InvalidArgumentException("minLength doit être >= 0, reçu : {$min}");
        }
        return $this->addRule('min_length', $min, 'include', true, 'or');
    }

    /** La ligne doit avoir au plus $max caractères (mb_strlen). */
    public function maxLength(int $max): self
    {
        if ($max < 0) {
            throw new InvalidArgumentException("maxLength doit être >= 0, reçu : {$max}");
        }
        return $this->addRule('max_length', $max, 'include', true, 'or');
    }

    // =========================================================================
    // Règles exclusives — la ligne NE DOIT PAS satisfaire la condition
    // =========================================================================

    /**
     * La ligne ne doit contenir aucune des valeurs ($and = false, défaut : OR).
     * Avec $and = true : interdite seulement si TOUTES les valeurs sont présentes simultanément.
     *
     * @param string|string[] $needle
     */
    public function excludes(
        string|array $needle,
        bool $caseSensitive = true,
        bool $and = false
    ): self {
        return $this->addRule('contains', $needle, 'exclude', $caseSensitive, $and ? 'and' : 'or');
    }

    /**
     * La ligne ne doit pas commencer par la valeur ou l'une des valeurs ($and = false).
     *
     * @param string|string[] $prefix
     */
    public function notStartsWith(
        string|array $prefix,
        bool $caseSensitive = true,
        bool $and = false
    ): self {
        return $this->addRule('starts_with', $prefix, 'exclude', $caseSensitive, $and ? 'and' : 'or');
    }

    /**
     * La ligne ne doit pas se terminer par la valeur ou l'une des valeurs ($and = false).
     *
     * @param string|string[] $suffix
     */
    public function notEndsWith(
        string|array $suffix,
        bool $caseSensitive = true,
        bool $and = false
    ): self {
        return $this->addRule('ends_with', $suffix, 'exclude', $caseSensitive, $and ? 'and' : 'or');
    }

    /**
     * La ligne ne doit PAS correspondre à l'expression régulière.
     *
     * @example notMatches('/health.?check/i')
     */
    public function notMatches(string $pattern): self
    {
        self::assertValidRegex($pattern);
        return $this->addRule('regex', $pattern, 'exclude', true, 'or');
    }

    /**
     * La ligne ne doit pas satisfaire le prédicat callable.
     *
     * @param callable(string): bool $predicate
     */
    public function whereNot(callable $predicate): self
    {
        return $this->addRule('callable', $predicate, 'exclude', true, 'or');
    }

    // =========================================================================
    // Évaluation
    // =========================================================================

    /**
     * Évalue toutes les règles sur une ligne.
     * Retourne true si la ligne passe TOUTES les règles (AND global entre règles).
     * Le $and/OR ne joue qu'à l'intérieur d'une règle à tableau.
     */
    public function passes(string $line): bool
    {
        foreach ($this->rules as $rule) {
            $result   = $this->evaluate($rule, $line);
            $expected = ($rule['mode'] === 'include');

            if ($result !== $expected) {
                return false; // court-circuit au premier échec
            }
        }

        return true;
    }

    /** Retourne true si le filtre est vide (laisse tout passer). */
    public function isEmpty(): bool
    {
        return empty($this->rules);
    }

    /** Nombre de règles enregistrées. */
    public function count(): int
    {
        return count($this->rules);
    }

    /**
     * Résumé des règles pour le débogage.
     *
     * @return array<int, array{mode: string, type: string, value: mixed, match: string, case: bool}>
     */
    public function describe(): array
    {
        return array_map(fn(array $r) => [
            'mode'  => $r['mode'],
            'type'  => $r['type'],
            'value' => is_callable($r['value']) ? '[callable]' : $r['value'],
            'match' => $r['match'],
            'case'  => $r['case'],
        ], $this->rules);
    }

    // =========================================================================
    // Interne
    // =========================================================================

    /**
     * Fabrique une nouvelle instance avec la règle ajoutée.
     *
     * @param 'or'|'and' $match
     */
    private function addRule(
        string $type,
        mixed $value,
        string $mode,
        bool $caseSensitive,
        string $match
    ): self {
        // Normaliser en tableau pour simplifier l'évaluation dans evaluate()
        $normalized = match ($type) {
            'contains', 'starts_with', 'ends_with' => is_array($value) ? $value : [$value],
            default                                 => $value,
        };

        // Ignorer les règles avec tableau vide (appelant qui passe [] par erreur)
        if (is_array($normalized) && empty($normalized)) {
            return $this;
        }

        $rules   = $this->rules;
        $rules[] = [
            'type'  => $type,
            'value' => $normalized,
            'mode'  => $mode,
            'case'  => $caseSensitive,
            'match' => $match,
        ];

        return new self($rules);
    }

    /**
     * Évalue une règle et retourne le résultat brut (sans le mode include/exclude).
     */
    private function evaluate(array $rule, string $line): bool
    {
        $cs    = $rule['case'];
        $value = $rule['value'];
        $match = $rule['match'];

        $subject = $cs ? $line : mb_strtolower($line);

        return match ($rule['type']) {

            'contains' => $match === 'and'
                ? self::allContained($subject, $value, $cs)
                : self::anyContained($subject, $value, $cs),

            'starts_with' => $match === 'and'
                ? self::allStartWith($subject, $value, $cs)
                : self::anyStartsWith($subject, $value, $cs),

            'ends_with' => $match === 'and'
                ? self::allEndWith($subject, $value, $cs)
                : self::anyEndsWith($subject, $value, $cs),

            'regex'      => (bool) preg_match($value, $line),

            'callable'   => (bool) ($value)($line),

            'not_empty'  => trim($line) !== '',

            'min_length' => mb_strlen($line) >= $value,

            'max_length' => mb_strlen($line) <= $value,

            default => throw new \LogicException("Type de règle inconnu : {$rule['type']}"),
        };
    }

    // =========================================================================
    // Helpers de correspondance
    // =========================================================================

    private static function anyContained(string $subject, array $needles, bool $cs): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($subject, $cs ? $needle : mb_strtolower($needle))) {
                return true;
            }
        }
        return false;
    }

    private static function allContained(string $subject, array $needles, bool $cs): bool
    {
        foreach ($needles as $needle) {
            if (!str_contains($subject, $cs ? $needle : mb_strtolower($needle))) {
                return false;
            }
        }
        return true;
    }

    private static function anyStartsWith(string $subject, array $prefixes, bool $cs): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($subject, $cs ? $prefix : mb_strtolower($prefix))) {
                return true;
            }
        }
        return false;
    }

    private static function allStartWith(string $subject, array $prefixes, bool $cs): bool
    {
        foreach ($prefixes as $prefix) {
            if (!str_starts_with($subject, $cs ? $prefix : mb_strtolower($prefix))) {
                return false;
            }
        }
        return true;
    }

    private static function anyEndsWith(string $subject, array $suffixes, bool $cs): bool
    {
        foreach ($suffixes as $suffix) {
            if (str_ends_with($subject, $cs ? $suffix : mb_strtolower($suffix))) {
                return true;
            }
        }
        return false;
    }

    private static function allEndWith(string $subject, array $suffixes, bool $cs): bool
    {
        foreach ($suffixes as $suffix) {
            if (!str_ends_with($subject, $cs ? $suffix : mb_strtolower($suffix))) {
                return false;
            }
        }
        return true;
    }

    private static function assertValidRegex(string $pattern): void
    {
        if (@preg_match($pattern, '') === false) {
            throw new InvalidArgumentException("Expression régulière invalide : {$pattern}");
        }
    }
}