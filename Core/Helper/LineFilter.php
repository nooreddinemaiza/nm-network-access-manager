<?php

namespace Core\Helper;

use InvalidArgumentException;

/**
 * LineFilter — Constructeur de filtres de lignes chaînable et immutable
 *
 * ARCHITECTURE PERFORMANCE
 * ─────────────────────────
 * Les règles sont "compilées" une seule fois en regex via compile() avant
 * la lecture. Toutes les chaînes d'un même groupe (include-contains,
 * exclude-contains…) sont fusionnées en un seul pattern alterné :
 *
 *   ['ERROR','WARN','CRIT']  →  /ERROR|WARN|CRIT/i
 *
 * Résultat : au lieu de N appels str_contains() par ligne, un seul
 * preg_match() dispatché par le moteur PCRE en C — ~10× plus rapide
 * sur des tableaux de 50+ termes.
 *
 * Usage
 * ─────
 *   $filter = LineFilter::new()
 *       ->contains(['ERROR', 'CRITICAL'])
 *       ->excludes(['staging', 'test'], caseSensitive: false)
 *       ->notMatches('/health.?check/i')
 *       ->compile();                     // ← toujours compiler avant la lecture
 *
 *   foreach (FileReader::readFiltered('log', 'dns.log', $filter) as $line) { … }
 */
final class LineFilter
{
    // -------------------------------------------------------------------------
    // Règles brutes (avant compilation)
    // -------------------------------------------------------------------------

    /** @var array<array{type:string, value:mixed, mode:string, case:bool, match:string}> */
    private array $rules = [];

    // -------------------------------------------------------------------------
    // État compilé (null = pas encore compilé)
    // -------------------------------------------------------------------------

    /**
     * Patterns PCRE compilés, indexés par "mode-type[-cs]".
     * Chaque entrée est un pattern prêt à passer à preg_match().
     *
     * @var array<string, string>|null
     */
    private ?array $compiled = null;

    /**
     * Callables et règles scalaires (min/max/not_empty) séparés des patterns :
     * ils ne sont pas compilables en regex.
     *
     * @var array<array{type:string, value:mixed, mode:string}>
     */
    private array $compiledScalar = [];

    private function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    // =========================================================================
    // Point d'entrée
    // =========================================================================

    public static function new(): self
    {
        return new self();
    }

    // =========================================================================
    // Règles inclusives
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
        return $this->add('contains', $needle, 'include', $caseSensitive, $and ? 'and' : 'or');
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
        return $this->add('starts_with', $prefix, 'include', $caseSensitive, $and ? 'and' : 'or');
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
        return $this->add('ends_with', $suffix, 'include', $caseSensitive, $and ? 'and' : 'or');
    }

    /**
     * La ligne doit correspondre à l'expression régulière.
     * Le pattern doit inclure ses délimiteurs : '/pattern/flags'
     */
    public function matches(string $pattern): self
    {
        self::assertValidRegex($pattern);
        return $this->add('regex_raw', $pattern, 'include', true, 'or');
    }

    /**
     * La ligne doit satisfaire le prédicat callable.
     *
     * @param callable(string): bool $predicate
     */
    public function where(callable $predicate): self
    {
        return $this->add('callable', $predicate, 'include', true, 'or');
    }

    /** La ligne ne doit pas être vide (après trim). */
    public function notEmpty(): self
    {
        return $this->add('not_empty', null, 'include', true, 'or');
    }

    /** La ligne doit avoir au moins $min caractères (mb_strlen). */
    public function minLength(int $min): self
    {
        if ($min < 0) throw new InvalidArgumentException("minLength >= 0 requis");
        return $this->add('min_length', $min, 'include', true, 'or');
    }

    /** La ligne doit avoir au plus $max caractères (mb_strlen). */
    public function maxLength(int $max): self
    {
        if ($max < 0) throw new InvalidArgumentException("maxLength >= 0 requis");
        return $this->add('max_length', $max, 'include', true, 'or');
    }

    // =========================================================================
    // Règles exclusives
    // =========================================================================

    /**
     * La ligne ne doit contenir aucune des valeurs ($and=false) ou
     * pas toutes simultanément ($and=true).
     *
     * @param string|string[] $needle
     */
    public function excludes(
        string|array $needle,
        bool $caseSensitive = true,
        bool $and = false
    ): self {
        return $this->add('contains', $needle, 'exclude', $caseSensitive, $and ? 'and' : 'or');
    }

    /**
     * La ligne ne doit pas commencer par la valeur ou l'une des valeurs.
     *
     * @param string|string[] $prefix
     */
    public function notStartsWith(
        string|array $prefix,
        bool $caseSensitive = true,
        bool $and = false
    ): self {
        return $this->add('starts_with', $prefix, 'exclude', $caseSensitive, $and ? 'and' : 'or');
    }

    /**
     * La ligne ne doit pas se terminer par la valeur ou l'une des valeurs.
     *
     * @param string|string[] $suffix
     */
    public function notEndsWith(
        string|array $suffix,
        bool $caseSensitive = true,
        bool $and = false
    ): self {
        return $this->add('ends_with', $suffix, 'exclude', $caseSensitive, $and ? 'and' : 'or');
    }

    /** La ligne ne doit PAS correspondre à l'expression régulière. */
    public function notMatches(string $pattern): self
    {
        self::assertValidRegex($pattern);
        return $this->add('regex_raw', $pattern, 'exclude', true, 'or');
    }

    /**
     * La ligne ne doit pas satisfaire le prédicat callable.
     *
     * @param callable(string): bool $predicate
     */
    public function whereNot(callable $predicate): self
    {
        return $this->add('callable', $predicate, 'exclude', true, 'or');
    }

    // =========================================================================
    // Compilation
    // =========================================================================

    /**
     * Compile toutes les règles "contains / starts_with / ends_with" en patterns
     * PCRE fusionnés. DOIT être appelé une fois avant de passer le filtre à
     * FileReader::readFiltered().
     *
     * Si compile() n'est pas appelé, passes() compile à la volée au premier appel.
     *
     * @return $this  (même instance, mutée — la compilation est un effet terminal)
     */
    public function compile(): self
    {
        if ($this->compiled !== null) {
            return $this; // déjà compilé
        }

        $this->compiled      = [];
        $this->compiledScalar = [];

        /*
         * Stratégie de fusion :
         *
         * On regroupe les règles compilables par clé de groupe :
         *   "{mode}-{type}-{cs}-{match}"
         *
         * Règles compilables (→ regex PCRE) :
         *   contains    : termes quelconques
         *   starts_with : ancrés en début de ligne (^)
         *   ends_with   : ancrés en fin de ligne ($)
         *
         * Règles non compilables (→ liste séparée) :
         *   regex_raw   : pattern déjà fourni tel quel
         *   callable    : fonction arbitraire
         *   not_empty   : vérification trim
         *   min_length  : longueur
         *   max_length  : longueur
         *
         * Pour les règles AND avec tableau : on génère un lookahead pour chaque terme.
         * Pour les règles OR  avec tableau : on génère une alternance simple.
         */

        /** @var array<string, list<string>> termes à fusionner par groupe */
        $groups = [];

        foreach ($this->rules as $rule) {
            $type  = $rule['type'];
            $mode  = $rule['mode'];
            $cs    = $rule['case'];
            $match = $rule['match'];
            $value = $rule['value'];

            // Règles non compilables → liste scalaire
            if (in_array($type, ['regex_raw', 'callable', 'not_empty', 'min_length', 'max_length'], true)) {
                $this->compiledScalar[] = $rule;
                continue;
            }

            // Normaliser en tableau
            $terms = is_array($value) ? $value : [$value];

            $groupKey = "{$mode}-{$type}-" . ($cs ? '1' : '0') . "-{$match}";
            $groups[$groupKey] = array_merge($groups[$groupKey] ?? [], $terms);
        }

        // Construire un pattern PCRE par groupe
        foreach ($groups as $groupKey => $terms) {
            [$mode, $type, $csStr, $match] = explode('-', $groupKey, 4);
            $cs      = $csStr === '1';
            $flags   = $cs ? '' : 'i';
            $escaped = array_map('preg_quote', $terms, array_fill(0, count($terms), '/'));

            if ($match === 'or') {
                // Alternance simple : ERROR|WARN|CRIT
                $alternation = implode('|', $escaped);
                $pattern = match ($type) {
                    'contains'    => "/(?:{$alternation})/{$flags}",
                    'starts_with' => "/^(?:{$alternation})/{$flags}",
                    'ends_with'   => "/(?:{$alternation})\$/{$flags}",
                };
            } else {
                // AND : lookaheads cumulés — chaque terme doit être présent
                // Pour starts_with/ends_with AND c'est rare mais cohérent
                $lookaheads = implode('', array_map(fn(string $e) => "(?=.*{$e})", $escaped));
                $pattern = match ($type) {
                    'contains'    => "/^{$lookaheads}/{$flags}s",   // s = . matche \n
                    'starts_with' => "/^" . implode('', array_map(fn(string $e) => "(?={$e})", $escaped)) . "/{$flags}",
                    'ends_with'   => "/(?:" . implode(').*?(?:', $escaped) . ")\$/{$flags}",
                };
            }

            // Stocker le pattern avec son mode (include/exclude) et son type
            $this->compiled[] = [
                'pattern' => $pattern,
                'mode'    => $mode,
                'type'    => $type,
                'match'   => $match,
            ];
        }

        return $this;
    }

    // =========================================================================
    // Évaluation
    // =========================================================================

    /**
     * Évalue toutes les règles compilées sur une ligne.
     * Court-circuite au premier échec (AND global entre règles).
     */
    public function passes(string $line): bool
    {
        // Auto-compilation si oubliée
        if ($this->compiled === null) {
            $this->compile();
        }

        // ---- Patterns compilés (preg_match, rapide) ----
        foreach ($this->compiled as $c) {
            $matched  = (bool) preg_match($c['pattern'], $line);
            $expected = ($c['mode'] === 'include');
            if ($matched !== $expected) {
                return false;
            }
        }

        // ---- Règles scalaires (callables, longueurs, etc.) ----
        foreach ($this->compiledScalar as $rule) {
            if (!$this->evaluateScalar($rule, $line)) {
                return false;
            }
        }

        return true;
    }

    /** Retourne true si le filtre est vide (laisse tout passer). */
    public function isEmpty(): bool
    {
        return empty($this->rules);
    }

    /** Nombre de règles déclarées (avant compilation). */
    public function count(): int
    {
        return count($this->rules);
    }

    /**
     * Résumé des patterns compilés pour le débogage.
     * Appeler après compile().
     *
     * @return array<int, array{pattern: string, mode: string}>
     */
    public function describe(): array
    {
        if ($this->compiled === null) {
            $this->compile();
        }

        $out = [];
        foreach ($this->compiled as $c) {
            $out[] = ['pattern' => $c['pattern'], 'mode' => $c['mode']];
        }
        foreach ($this->compiledScalar as $r) {
            $out[] = [
                'pattern' => is_callable($r['value']) ? '[callable]' : "[{$r['type']}:{$r['value']}]",
                'mode'    => $r['mode'],
            ];
        }
        return $out;
    }

    // =========================================================================
    // Interne
    // =========================================================================

    private function add(
        string $type,
        mixed $value,
        string $mode,
        bool $cs,
        string $match
    ): self {
        $normalized = match ($type) {
            'contains', 'starts_with', 'ends_with' => is_array($value) ? $value : [$value],
            default                                 => $value,
        };

        if (is_array($normalized) && empty($normalized)) {
            return $this;
        }

        $clone          = clone $this;
        $clone->rules[] = compact('type', 'mode', 'cs', 'match') + ['value' => $normalized, 'case' => $cs];
        $clone->compiled = null; // invalider la compilation précédente
        return $clone;
    }

    /**
     * Évalue les règles non compilables (callables, longueurs, etc.).
     */
    private function evaluateScalar(array $rule, string $line): bool
    {
        $result = match ($rule['type']) {
            'regex_raw'  => (bool) preg_match($rule['value'], $line),
            'callable'   => (bool) ($rule['value'])($line),
            'not_empty'  => trim($line) !== '',
            'min_length' => mb_strlen($line) >= $rule['value'],
            'max_length' => mb_strlen($line) <= $rule['value'],
            default      => throw new \LogicException("Type inconnu : {$rule['type']}"),
        };

        $expected = ($rule['mode'] === 'include');
        return $result === $expected;
    }

    private static function assertValidRegex(string $pattern): void
    {
        if (@preg_match($pattern, '') === false) {
            throw new InvalidArgumentException("Regex invalide : {$pattern}");
        }
    }
}