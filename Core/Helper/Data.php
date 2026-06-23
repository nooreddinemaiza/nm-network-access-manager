<?php

namespace Core\Helper;

use Countable;
use ArrayAccess;
use Traversable;
use JsonSerializable;
use IteratorAggregate;
use InvalidArgumentException;

/**
 * Classe Data légère pour la manipulation de données
 * Autonome, sans dépendances externes
 */
class Data implements ArrayAccess, JsonSerializable, IteratorAggregate, Countable
{
    protected array $data = [];
    protected string $delimiter = '.';

    protected static array $labels = [
        'email' => "l'email",
        'password' => "le mot de passe",
        'username' => "le nom d'utilisateur",
        'fullname' => "le nom complet",
        'name' => "le nom",
        'phone' => "le téléphone",
    ];

    public function __construct(mixed $input = [], string $delimiter = '.')
    {
        $this->delimiter = $delimiter;
        $this->load($input);
    }

    public static function create(mixed $input = [], string $delimiter = '.'): self
    {
        return new self($input, $delimiter);
    }

    public function load(mixed $input): self
    {
        if (is_string($input)) {
            $input = trim($input);
            if ($this->isJson($input)) {
                $this->data = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
            } elseif ($this->isSerializedData($input)) {
                $this->data = unserialize($input);
            } else {
                throw new InvalidArgumentException('Chaîne invalide (JSON ou serialized attendu)');
            }
        } elseif (is_array($input)) {
            $this->data = $input;
        } elseif (is_object($input)) {
            $this->data = $input instanceof self ? $input->all() : get_object_vars($input);
        } else {
            $this->data = [];
        }

        return $this;
    }

    // ==================== ACCÈS DE BASE ====================

    public function get(string $key, mixed $default = null): mixed
    {
        if (empty($key)) return $default;

        $value = $this->data;
        foreach (explode($this->delimiter, $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function set(string $key, mixed $value): self
    {
        if (empty($key)) {
            throw new InvalidArgumentException('La clé ne peut pas etre vide');
        }

        $segments = explode($this->delimiter, $key);
        $ref = &$this->data;

        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        $ref = $value;
        return $this;
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__MISSING__') !== '__MISSING__';
    }

    public function remove(string $key): self
    {
        if (empty($key)) return $this;

        $segments = explode($this->delimiter, $key);
        $ref = &$this->data;

        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                return $this;
            }
            $ref = &$ref[$segment];
        }

        unset($ref[array_shift($segments)]);
        return $this;
    }

    public function forget(string|array $keys): self
    {
        foreach ((array)$keys as $key) {
            $this->remove($key);
        }
        return $this;
    }

    // ==================== MANIPULATION ====================

    public function push(string $key, mixed $value): self
    {
        $existing = $this->get($key, []);
        if (!is_array($existing)) {
            throw new InvalidArgumentException("Clé {$key} n'est pas un tableau");
        }
        $existing[] = $value;
        return $this->set($key, $existing);
    }

    public function merge(string $key, array $values): self
    {
        $existing = $this->get($key, []);
        return $this->set($key, array_merge((array)$existing, $values));
    }

    public function increment(string $key, int|float $amount = 1): self
    {
        $current = $this->get($key, 0);
        return $this->set($key, $current + $amount);
    }

    public function decrement(string $key, int|float $amount = 1): self
    {
        return $this->increment($key, -$amount);
    }

    // ==================== NOUVEAUTÉS: GESTION DES VALEURS NULLES/VIDES ====================

    /**
     * Retire tous les champs null de manière récursive
     */
    public function removeNulls(): self
    {
        $this->data = $this->removeNullsRecursive($this->data);
        return $this;
    }

    /**
     * Retire tous les champs vides (null, '', [], false) de manière récursive
     */
    public function removeEmpty(): self
    {
        $this->data = $this->removeEmptyRecursive($this->data);
        return $this;
    }

    /**
     * Retire les champs selon un callback personnalisé
     */
    public function removeWhere(callable $callback): self
    {
        $this->data = $this->removeWhereRecursive($this->data, $callback);
        return $this;
    }

    protected function removeNullsRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->removeNullsRecursive($value);
            }
        }
        return $data;
    }

    protected function removeEmptyRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->removeEmptyRecursive($value);
                if (empty($data[$key])) {
                    unset($data[$key]);
                }
            } elseif ($this->isEmptyValue($value)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    protected function removeWhereRecursive(array $data, callable $callback): array
    {
        foreach ($data as $key => $value) {
            if ($callback($value, $key)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->removeWhereRecursive($value, $callback);
            }
        }
        return $data;
    }

    /**
     * Vérifie si une valeur est considérée comme vide
     */
    protected function isEmptyValue(mixed $value): bool
    {
        return $value === null
            || $value === ''
            || $value === []
            || $value === false;
    }

    /**
     * Vérifie si TOUS les champs sont vides (récursif)
     */
    public function isCompletelyEmpty(): bool
    {
        return $this->isCompletelyEmptyRecursive($this->data);
    }

    protected function isCompletelyEmptyRecursive(mixed $data): bool
    {
        if (!is_array($data)) {
            return $this->isEmptyValue($data);
        }

        if (empty($data)) {
            return true;
        }

        foreach ($data as $value) {
            if (!$this->isCompletelyEmptyRecursive($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie si AU MOINS UN champ est vide
     */
    public function hasEmptyFields(): bool
    {
        return $this->hasEmptyFieldsRecursive($this->data);
    }

    protected function hasEmptyFieldsRecursive(mixed $data): bool
    {
        if (!is_array($data)) {
            return $this->isEmptyValue($data);
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                if ($this->hasEmptyFieldsRecursive($value)) {
                    return true;
                }
            } elseif ($this->isEmptyValue($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compte les champs vides
     */
    public function countEmpty(): int
    {
        return $this->countEmptyRecursive($this->data);
    }

    protected function countEmptyRecursive(array $data): int
    {
        $count = 0;
        foreach ($data as $value) {
            if (is_array($value)) {
                $count += $this->countEmptyRecursive($value);
            } elseif ($this->isEmptyValue($value)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Compte les champs non vides
     */
    public function countFilled(): int
    {
        return $this->countFilledRecursive($this->data);
    }

    protected function countFilledRecursive(array $data): int
    {
        $count = 0;
        foreach ($data as $value) {
            if (is_array($value)) {
                $count += $this->countFilledRecursive($value);
            } elseif (!$this->isEmptyValue($value)) {
                $count++;
            }
        }
        return $count;
    }

    // ==================== NOUVEAUTÉS: UTILITAIRES SUPPLÉMENTAIRES ====================

    /**
     * Applique une valeur par défaut aux champs vides
     */
    public function fillEmpty(mixed $defaultValue): self
    {
        $this->data = $this->fillEmptyRecursive($this->data, $defaultValue);
        return $this;
    }

    protected function fillEmptyRecursive(array $data, mixed $defaultValue): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->fillEmptyRecursive($value, $defaultValue);
            } elseif ($this->isEmptyValue($value)) {
                $data[$key] = $defaultValue;
            }
        }
        return $data;
    }

    /**
     * Clone profond de l'objet Data
     */
    public function clone(): self
    {
        return new self($this->data, $this->delimiter);
    }

    /**
     * Flatten un tableau multidimensionnel
     */
    public function flatten(string $separator = '.'): self
    {
        return new self($this->flattenArray($this->data, '', $separator), $this->delimiter);
    }

    protected function flattenArray(array $data, string $prefix = '', string $separator = '.'): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = $prefix ? $prefix . $separator . $key : $key;
            if (is_array($value) && !empty($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey, $separator));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Transforme les clés en snake_case
     */
    public function keysToSnakeCase(): self
    {
        return new self($this->transformKeys($this->data, 'snake'), $this->delimiter);
    }

    /**
     * Transforme les clés en camelCase
     */
    public function keysToCamelCase(): self
    {
        return new self($this->transformKeys($this->data, 'camel'), $this->delimiter);
    }

    protected function transformKeys(array $data, string $type): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = $type === 'snake' ? $this->toSnakeCase($key) : $this->toCamelCase($key);
            $result[$newKey] = is_array($value) ? $this->transformKeys($value, $type) : $value;
        }
        return $result;
    }

    protected function toSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    protected function toCamelCase(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    /**
     * Recherche récursive d'une valeur
     */
    public function search(mixed $value, bool $strict = false): array
    {
        return $this->searchRecursive($this->data, $value, $strict);
    }

    protected function searchRecursive(array $data, mixed $needle, bool $strict, string $prefix = ''): array
    {
        $results = [];
        foreach ($data as $key => $value) {
            $currentKey = $prefix ? $prefix . $this->delimiter . $key : $key;
            if (is_array($value)) {
                $results = array_merge($results, $this->searchRecursive($value, $needle, $strict, $currentKey));
            } elseif (($strict && $value === $needle) || (!$strict && $value == $needle)) {
                $results[] = $currentKey;
            }
        }
        return $results;
    }

    /**
     * Déduplique les valeurs d'un tableau
     */
    public function unique(?string $key = null): self
    {
        if ($key === null) {
            return new self(array_unique($this->data, SORT_REGULAR), $this->delimiter);
        }

        $seen = [];
        $unique = [];
        foreach ($this->data as $item) {
            $value = is_array($item) ? ($item[$key] ?? null) : null;
            if (!in_array($value, $seen, true)) {
                $seen[] = $value;
                $unique[] = $item;
            }
        }
        return new self($unique, $this->delimiter);
    }

    /**
     * Groupe les éléments par clé
     */
    public function groupBy(string $key): self
    {
        $result = [];
        foreach ($this->data as $item) {
            $groupKey = is_array($item) ? ($item[$key] ?? 'undefined') : 'undefined';
            $result[$groupKey][] = $item;
        }
        return new self($result, $this->delimiter);
    }

    /**
     * Extrait une colonne spécifique
     */
    public function pluck(string $valueKey, ?string $keyKey = null): array
    {
        if ($keyKey === null) {
            return array_column($this->data, $valueKey);
        }
        return array_column($this->data, $valueKey, $keyKey);
    }

    // ==================== COLLECTION ====================

    public function only(array $keys): self
    {
        $result = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->get($key);
            }
        }
        return new self($result, $this->delimiter);
    }

    public function except(array $keys): self
    {
        $result = $this->data;
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        return new self($result, $this->delimiter);
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->data, array_keys($this->data)), $this->delimiter);
    }

    public function filter(?callable $callback = null): self
    {
        $result = $callback
            ? array_filter($this->data, $callback, ARRAY_FILTER_USE_BOTH)
            : array_filter($this->data);
        return new self($result, $this->delimiter);
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->data, $callback, $initial);
    }

    public function where(string $key, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $itemValue = is_array($item) ? ($item[$key] ?? null) : null;
            return match ($operator) {
                '=' => $itemValue == $value,
                '!=' => $itemValue != $value,
                '>' => $itemValue > $value,
                '<' => $itemValue < $value,
                '>=' => $itemValue >= $value,
                '<=' => $itemValue <= $value,
                default => false
            };
        });
    }

    public function sort(?callable $callback = null): self
    {
        $data = $this->data;
        $callback ? uasort($data, $callback) : asort($data);
        return new self($data, $this->delimiter);
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return empty($this->data) ? $default : reset($this->data);
        }
        foreach ($this->data as $key => $value) {
            if ($callback($value, $key)) return $value;
        }
        return $default;
    }

    public function take(int $count): self
    {
        return new self(array_slice($this->data, 0, $count, true), $this->delimiter);
    }

    public function chunk(int $size): self
    {
        return new self(array_chunk($this->data, $size, true), $this->delimiter);
    }

    // ==================== ACCÈS TYPÉ ====================

    public function asString(string $key, string $default = ''): string
    {
        return (string)($this->get($key) ?? $default);
    }

    public function asInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        return is_numeric($value) ? (int)$value : $default;
    }

    public function asFloat(string $key, float $default = 0.0): float
    {
        $value = $this->get($key, $default);
        return is_numeric($value) ? (float)$value : $default;
    }

    public function asBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function asArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);
        return is_array($value) ? $value : $default;
    }

    // ==================== ÉTAT ====================

    /**
     * @deprecated Utilisez isCompletelyEmpty() pour tester si tous les champs sont vides
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function keys(): array
    {
        return array_keys($this->data);
    }

    public function values(): array
    {
        return array_values($this->data);
    }

    // ==================== VALIDATION ====================

    public function validate(array $rules): array
    {
        $errors = [];
        foreach ($rules as $key => $ruleSet) {
            $keyErrors = $this->validateValue($this->get($key), $ruleSet, $key);
            if (!empty($keyErrors)) {
                $errors[$key] = $keyErrors;
            }
        }
        return $errors;
    }

    public function validateValue(mixed $value, array|string $rules, string $key): array
    {
        $errors = [];
        $rules = is_string($rules) ? explode('|', $rules) : $rules;
        $label = ucfirst(self::$labels[$key] ?? $key);

        // Si la valeur est vide et que le champ n'est pas requis, on passe
        if (($value === null || $value === '') && !in_array('required', $rules)) {
            return $errors;
        }

        foreach ($rules as $rule) {
            [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

            $error = match ($ruleName) {
                // Validations de base
                'required'   => ($value === null || $value === '') ? "{$label} est requis" : null,
                'array'   => !is_array($value) ? "{$label} invalide"  : null,
                'string'     => !is_string($value) ? "{$label} doit etre une chaîne" : null,
                'email'      => !filter_var($value, FILTER_VALIDATE_EMAIL) ? "{$label} invalide" : null,
                'numeric' => !preg_match('/^\d+(\.\d+)?$/', (string) $value)
                    ? "{$label} doit etre un nombre valide"
                    : null,
                'integer'    => !filter_var($value, FILTER_VALIDATE_INT) ? "{$label} doit etre un entier" : null,
                'url'        => !filter_var($value, FILTER_VALIDATE_URL) ? "{$label} invalide" : null,
                'boolean'    => !is_bool($value) && !in_array($value, [0, 1, '0', '1', true, false], true)
                    ? "{$label} doit etre un booléen" : null,

                // Validations de taille
                'min'        => $this->validateMin($value, $param, $label),
                'max'        => $this->validateMax($value, $param, $label),
                'between'    => $this->validateBetween($value, $param, $label),
                'size'       => $this->validateSize($value, $param, $label),

                // Validations de comparaison
                'confirm'    => $this->get($key) !== $this->get($param) ? 'La confirmation est incorrecte!' : null,
                'same'       => $this->get($key) !== ($param) ? "{$label} doit etre identique à " . $param : null,
                'different'  => $this->get($key) === ($param) ? "{$label} doit etre différent de " . $param : null,

                // Validations de format
                'regex'      => !preg_match($param, $value) ? "Format de {$label} invalide" : null,
                'in'         => !in_array($value, explode(',', $param)) ? "{$label} invalide" : null,
                'not_in'     => in_array($value, explode(',', $param)) ? "{$label} contient une valeur non autorisée" : null,
                'alpha'      => !ctype_alpha($value) ? "{$label} doit contenir uniquement des lettres" : null,
                'alpha_num'  => !ctype_alnum($value) ? "{$label} doit etre alphanumérique" : null,
                'alpha_dash' => !preg_match('/^[a-zA-Z0-9_-]+$/', $value) ? "{$label} ne peut contenir que lettres, chiffres, tirets et underscores" : null,

                // Validations spécifiques
                'username'   => !preg_match('/^[a-zA-Z][a-zA-Z0-9_]{2,31}$/', $value)
                    ? "{$label} invalide (3-32 caractères, commence par une lettre)"
                    : null,
                'phone'      => !preg_match('/^(\+|00)?[0-9]{8,15}$/', $value)
                    ? "{$label} invalide (format: +212612345678)" : null,
                'postal_code' => !preg_match('/^[0-9]{5}$/', $value)
                    ? "{$label} invalide (5 chiffres)" : null,
                'ip'         => !filter_var($value, FILTER_VALIDATE_IP) ? "{$label} invalide" : null,
                'ipv4'       => !filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? "{$label} doit etre une IPv4 valide" : null,
                'ipv6'       => !filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? "{$label} doit etre une IPv6 valide" : null,
                'mac'        => !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value)
                    ? "{$label} invalide (format: AA:BB:CC:DD:EE:FF)" : null,
                'json'       => !$this->isValidJson($value) ? "{$label} doit etre un JSON valide" : null,
                'uuid'       => !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)
                    ? "{$label} doit etre un UUID valide" : null,

                // Validations de dates
                'date'       => $this->validateDate($value, $label),
                'date_format' => $this->validateDateFormat($value, $param, $label),
                'datetime'   => $this->validateDateTime($value, $label),
                'time'       => $this->validateTime($value, $label),
                'before'     => $this->validateBefore($value, $param, $label),
                'before_or_equal' => $this->validateBeforeOrEqual($value, $param, $label),
                'after'      => $this->validateAfter($value, $param, $label),
                'after_or_equal' => $this->validateAfterOrEqual($value, $param, $label),
                'date_equals' => $this->validateDateEquals($value, $param, $label),
                'timezone'   => !in_array($value, timezone_identifiers_list())
                    ? "{$label} doit etre un fuseau horaire valide" : null,

                // Validations numériques avancées
                'positive'   => (!is_numeric($value) || $value <= 0) ? "{$label} doit etre positif" : null,
                'negative'   => (!is_numeric($value) || $value >= 0) ? "{$label} doit etre négatif" : null,
                'gt'         => (!is_numeric($value) || $value <= $param) ? "{$label} doit etre supérieur à {$param}" : null,
                'gte'        => (!is_numeric($value) || $value < $param) ? "{$label} doit etre supérieur ou égal à {$param}" : null,
                'lt'         => (!is_numeric($value) || $value >= $param) ? "{$label} doit etre inférieur à {$param}" : null,
                'lte'        => (!is_numeric($value) || $value > $param) ? "{$label} doit etre inférieur ou égal à {$param}" : null,
                'decimal'    => !preg_match('/^-?\d+(\.\d+)?$/', $value) ? "{$label} doit etre un nombre décimal" : null,
                'multiple_of' => (!is_numeric($value) || fmod($value, $param) != 0)
                    ? "{$label} doit etre un multiple de {$param}" : null,

                // Validations de fichiers (si applicable)
                'file'       => !$this->isValidFile($value) ? "{$label} doit etre un fichier valide" : null,
                'image'      => !$this->isValidImage($value) ? "{$label} doit etre une image valide" : null,
                'mimes'      => !$this->validateMimes($value, $param) ? "{$label} doit etre de type: {$param}" : null,
                'max_size'   => !$this->validateMaxSize($value, $param) ? "{$label} ne doit pas dépasser {$param}KB" : null,

                // Validations de contenu
                'starts_with' => !str_starts_with($value, $param) ? "{$label} doit commencer par '{$param}'" : null,
                'ends_with'  => !str_ends_with($value, $param) ? "{$label} doit se terminer par '{$param}'" : null,
                'contains'   => !str_contains($value, $param) ? "{$label} doit contenir '{$param}'" : null,
                'lowercase'  => $value !== strtolower($value) ? "{$label} doit etre en minuscules" : null,
                'uppercase'  => $value !== strtoupper($value) ? "{$label} doit etre en majuscules" : null,
                'no_spaces'  => preg_match('/\s/', $value) ? "{$label} ne doit pas contenir d'espaces" : null,

                // Validations de sécurité
                'no_html'    => $value !== strip_tags($value) ? "{$label} ne doit pas contenir de HTML" : null,
                'no_sql'     => $this->containsSqlInjection($value) ? "{$label} contient des caractères suspects" : null,
                'strong_password' => !$this->isStrongPassword($value)
                    ? "{$label} doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial"
                    : null,

                default => null
            };

            if ($error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Valide la taille minimum
     */
    private function validateMin(mixed $value, mixed $param, string $label): ?string
    {
        if (is_numeric($value)) {
            return $value < $param ? "{$label} doit etre au moins {$param}" : null;
        }
        if (is_string($value)) {
            return strlen($value) < $param ? "{$label} doit contenir au moins {$param} caractères" : null;
        }
        if (is_array($value)) {
            return count($value) < $param ? "{$label} doit contenir au moins {$param} éléments" : null;
        }
        return null;
    }

    /**
     * Valide la taille maximum
     */
    private function validateMax(mixed $value, mixed $param, string $label): ?string
    {
        if (is_numeric($value)) {
            return $value > $param ? "{$label} ne doit pas dépasser {$param}" : null;
        }
        if (is_string($value)) {
            return strlen($value) > $param ? "{$label} ne doit pas dépasser {$param} caractères" : null;
        }
        if (is_array($value)) {
            return count($value) > $param ? "{$label} ne doit pas contenir plus de {$param} éléments" : null;
        }
        return null;
    }

    /**
     * Valide que la valeur est entre deux limites
     */
    private function validateBetween(mixed $value, string $param, string $label): ?string
    {
        [$min, $max] = explode(',', $param);

        if (is_numeric($value)) {
            return ($value < $min || $value > $max)
                ? "{$label} doit etre entre {$min} et {$max}" : null;
        }
        if (is_string($value)) {
            $length = strlen($value);
            return ($length < $min || $length > $max)
                ? "{$label} doit contenir entre {$min} et {$max} caractères" : null;
        }
        return null;
    }

    /**
     * Valide la taille exacte
     */
    private function validateSize(mixed $value, mixed $param, string $label): ?string
    {
        if (is_numeric($value)) {
            return $value != $param ? "{$label} doit etre égal à {$param}" : null;
        }
        if (is_string($value)) {
            return strlen($value) != $param ? "{$label} doit contenir exactement {$param} caractères" : null;
        }
        if (is_array($value)) {
            return count($value) != $param ? "{$label} doit contenir exactement {$param} éléments" : null;
        }
        return null;
    }

    /**
     * Valide une date (YYYY-MM-DD)
     */
    private function validateDate(mixed $value, string $label): ?string
    {
        if (!is_string($value)) {
            return "{$label} doit etre une chaîne de caractères";
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return ($date && $date->format('Y-m-d') === $value)
            ? null : "{$label} doit etre une date valide (format: YYYY-MM-DD)";
    }

    /**
     * Valide une date avec un format personnalisé
     */
    private function validateDateFormat(mixed $value, string $format, string $label): ?string
    {
        if (!is_string($value)) {
            return "{$label} doit etre une chaîne de caractères";
        }

        $date = \DateTime::createFromFormat($format, $value);
        return ($date && $date->format($format) === $value)
            ? null : "{$label} doit correspondre au format: {$format}";
    }

    /**
     * Valide un datetime (YYYY-MM-DD HH:MM:SS)
     */
    private function validateDateTime(mixed $value, string $label): ?string
    {
        if (!is_string($value)) {
            return "{$label} doit etre une chaîne de caractères";
        }

        // Accepte les formats: YYYY-MM-DD HH:MM:SS ou YYYY-MM-DDTHH:MM:SS
        $formats = ['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return null;
            }
        }

        return "{$label} doit etre une date/heure valide (format: YYYY-MM-DD HH:MM:SS)";
    }

    /**
     * Valide une heure (HH:MM:SS ou HH:MM)
     */
    private function validateTime(mixed $value, string $label): ?string
    {
        if (!is_string($value)) {
            return "{$label} doit etre une chaîne de caractères";
        }

        if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)) {
            return null;
        }

        return "{$label} doit etre une heure valide (format: HH:MM ou HH:MM:SS)";
    }

    /**
     * Valide que la date est avant une autre date
     */
    private function validateBefore(mixed $value, string $param, string $label): ?string
    {
        try {
            $date = new \DateTime($value);
            $compareDate = $param === 'today' ? new \DateTime() : new \DateTime($param);

            return $date < $compareDate
                ? null : "{$label} doit etre avant " . ($param === 'today' ? "aujourd'hui" : $param);
        } catch (\Exception $e) {
            return "{$label} ou la date de comparaison est invalide";
        }
    }

    /**
     * Valide que la date est avant ou égale à une autre date
     */
    private function validateBeforeOrEqual(mixed $value, string $param, string $label): ?string
    {
        try {
            $date = new \DateTime($value);
            $compareDate = $param === 'today' ? new \DateTime() : new \DateTime($param);

            return $date <= $compareDate
                ? null : "{$label} doit etre avant ou égale à " . ($param === 'today' ? "aujourd'hui" : $param);
        } catch (\Exception $e) {
            return "{$label} ou la date de comparaison est invalide";
        }
    }

    /**
     * Valide que la date est après une autre date
     */
    private function validateAfter(mixed $value, string $param, string $label): ?string
    {
        try {
            $date = new \DateTime($value);
            $compareDate = $param === 'today' ? new \DateTime() : new \DateTime($param);

            return $date > $compareDate
                ? null : "{$label} doit etre après " . ($param === 'today' ? "aujourd'hui" : $param);
        } catch (\Exception $e) {
            return "{$label} ou la date de comparaison est invalide";
        }
    }

    /**
     * Valide que la date est après ou égale à une autre date
     */
    private function validateAfterOrEqual(mixed $value, string $param, string $label): ?string
    {
        try {
            $date = new \DateTime($value);
            $compareDate = $param === 'today' ? new \DateTime() : new \DateTime($param);

            return $date >= $compareDate
                ? null : "{$label} doit etre après ou égale à " . ($param === 'today' ? "aujourd'hui" : $param);
        } catch (\Exception $e) {
            return "{$label} ou la date de comparaison est invalide";
        }
    }

    /**
     * Valide que la date est égale à une autre date
     */
    private function validateDateEquals(mixed $value, string $param, string $label): ?string
    {
        try {
            $date = new \DateTime($value);
            $compareDate = $param === 'today' ? new \DateTime() : new \DateTime($param);

            return $date->format('Y-m-d') === $compareDate->format('Y-m-d')
                ? null : "{$label} doit etre égale à " . ($param === 'today' ? "aujourd'hui" : $param);
        } catch (\Exception $e) {
            return "{$label} ou la date de comparaison est invalide";
        }
    }

    /**
     * Vérifie si une chaîne est un JSON valide
     */
    private function isValidJson(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Vérifie si c'est un fichier valide
     */
    private function isValidFile(mixed $value): bool
    {
        return $value instanceof \SplFileInfo ||
            (is_array($value) && isset($value['tmp_name']) && is_uploaded_file($value['tmp_name']));
    }

    /**
     * Vérifie si c'est une image valide
     */
    private function isValidImage(mixed $value): bool
    {
        if (!$this->isValidFile($value)) {
            return false;
        }

        $path = is_array($value) ? $value['tmp_name'] : $value->getPathname();
        $imageInfo = @getimagesize($path);

        return $imageInfo !== false;
    }

    /**
     * Valide les types MIME
     */
    private function validateMimes(mixed $value, string $param): bool
    {
        if (!$this->isValidFile($value)) {
            return false;
        }

        $allowedMimes = explode(',', $param);
        $fileMime = is_array($value) ? $value['type'] : mime_content_type($value->getPathname());

        return in_array($fileMime, $allowedMimes);
    }

    /**
     * Valide la taille maximale d'un fichier
     */
    private function validateMaxSize(mixed $value, int $maxSizeKB): bool
    {
        if (!$this->isValidFile($value)) {
            return false;
        }

        $fileSize = is_array($value) ? $value['size'] : $value->getSize();

        return ($fileSize / 1024) <= $maxSizeKB;
    }

    /**
     * Détecte les tentatives d'injection SQL basiques
     */
    private function containsSqlInjection(string $value): bool
    {
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bor\b.*=.*)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(--|;|\/\*|\*\/)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le mot de passe est fort
     */
    private function isStrongPassword(string $value): bool
    {
        return strlen($value) >= 8 &&
            preg_match('/[A-Z]/', $value) &&      // Au moins une majuscule
            preg_match('/[a-z]/', $value) &&      // Au moins une minuscule
            preg_match('/[0-9]/', $value) &&      // Au moins un chiffre
            preg_match('/[^A-Za-z0-9]/', $value); // Au moins un caractère spécial
    }

    // ==================== SANITIZATION ====================

    public function sanitize(array $options = []): self
    {
        $opts = array_merge([
            'strip_tags' => true,
            'trim' => true,
            'htmlspecialchars' => true,
        ], $options);

        $sanitized = [];

        foreach ($this->data as $key => $value) {
            if (!is_string($value)) {
                $sanitized[$key] = $value;
                continue;
            }

            $sanitizedValue = $value;

            if ($opts['trim']) {
                $sanitizedValue = trim($sanitizedValue);
            }
            if ($opts['strip_tags']) {
                $sanitizedValue = strip_tags($sanitizedValue);
            }
            if ($opts['htmlspecialchars']) {
                $sanitizedValue = htmlspecialchars(
                    $sanitizedValue,
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );
            }

            $sanitized[$key] = $sanitizedValue;
        }

        return new self($sanitized, $this->delimiter);
    }

    // ==================== TRANSFORMATION ====================

    public function toLower(): self
    {
        return $this->map(fn($v) => is_string($v) ? mb_strtolower($v) : $v);
    }

    public function toUpper(): self
    {
        return $this->map(fn($v) => is_string($v) ? mb_strtoupper($v) : $v);
    }

    // ==================== STATISTIQUES ====================

    public function sum(?string $key = null): int|float
    {
        $values = $key ? array_column($this->data, $key) : $this->data;
        return array_sum(array_filter($values, 'is_numeric'));
    }

    public function avg(?string $key = null): float
    {
        $values = array_filter($key ? array_column($this->data, $key) : $this->data, 'is_numeric');
        return $values ? array_sum($values) / count($values) : 0;
    }

    public function min(?string $key = null): mixed
    {
        $values = array_filter($key ? array_column($this->data, $key) : $this->data, 'is_numeric');
        return $values ? min($values) : null;
    }

    public function max(?string $key = null): mixed
    {
        $values = array_filter($key ? array_column($this->data, $key) : $this->data, 'is_numeric');
        return $values ? max($values) : null;
    }

    // ==================== EXPORT ====================

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(int $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->data, $options | JSON_THROW_ON_ERROR);
    }

    public function serialize(): string
    {
        return serialize($this->data);
    }

    public function toObject(): object
    {
        $obj = new \stdClass();
        foreach ($this->data as $key => $value) {
            $obj->$key = $value;
        }
        return $obj;
    }

    // ==================== LABELS ====================

    public static function addLabel(array $labels): void
    {
        self::$labels = array_merge(self::$labels, $labels);
    }

    // ==================== INTERFACES ====================

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->data);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string)$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string)$offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $offset === null ? $this->data[] = $value : $this->set((string)$offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove((string)$offset);
    }

    // ==================== HELPERS ====================

    protected function isJson(string $string): bool
    {
        if (empty($string) || (!str_starts_with($string, '{') && !str_starts_with($string, '['))) {
            return false;
        }
        try {
            json_decode($string, true, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException) {
            return false;
        }
    }

    protected function isSerializedData(string $string): bool
    {
        return !empty($string) && (@unserialize($string) !== false || $string === 'b:0;');
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function __debugInfo(): array
    {
        return ['data' => $this->data, 'count' => $this->count()];
    }
}
