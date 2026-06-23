<?php

declare(strict_types=1);

namespace Core\Redis\Contracts;

/**
 * Abstraction d'une connexion Redis.
 *
 * Expose uniquement les commandes utilisées par le sous-système Queue.
 * Cela permet de changer l'implémentation sous-jacente (ext-redis, Predis,
 * MockRedis pour les tests…) sans toucher au RedisDriver.
 *
 * Toutes les méthodes lèvent une RedisException en cas d'erreur de connexion
 * ou d'exécution côté Redis.
 */
interface RedisConnectionInterface
{
    // -------------------------------------------------------------------------
    // Listes (queues principales et réservées)
    // -------------------------------------------------------------------------

    /**
     * Insère une ou plusieurs valeurs en tête de liste (LEFT PUSH).
     * Retourne la nouvelle longueur de la liste.
     */
    public function lPush(string $key, string ...$values): int;

    /**
     * Retire un élément en queue (RIGHT) et le pousse en tête (LEFT)
     * d'une autre liste, de façon atomique.
     * Retourne l'élément déplacé, ou null si la source est vide.
     */
    public function rPopLPush(string $source, string $destination): ?string;

    /**
     * Supprime `count` occurrences de `value` dans la liste.
     * Retourne le nombre d'éléments supprimés.
     */
    public function lRem(string $key, string $value, int $count = 1): int;

    /**
     * Retourne les éléments de la liste entre `start` et `stop` inclus.
     *
     * @return string[]
     */
    public function lRange(string $key, int $start, int $stop): array;

    /**
     * Retourne la longueur d'une liste.
     */
    public function lLen(string $key): int;

    // -------------------------------------------------------------------------
    // Sorted Sets (delayed jobs)
    // -------------------------------------------------------------------------

    /**
     * Ajoute un membre avec un score dans un Sorted Set.
     * Retourne le nombre d'éléments ajoutés.
     */
    public function zAdd(string $key, float $score, string $member): int;

    /**
     * Retourne les membres dont le score est compris entre $min et $max.
     *
     * @return string[]
     */
    public function zRangeByScore(string $key, string $min, string $max): array;

    /**
     * Supprime un ou plusieurs membres du Sorted Set.
     * Retourne le nombre d'éléments supprimés.
     */
    public function zRem(string $key, string ...$members): int;

    // -------------------------------------------------------------------------
    // Hashes (payloads des jobs)
    // -------------------------------------------------------------------------

    /**
     * Définit plusieurs champs d'un Hash en une seule commande.
     *
     * @param array<string, mixed> $fields
     */
    public function hMSet(string $key, array $fields): void;

    /**
     * Retourne tous les champs et valeurs d'un Hash.
     *
     * @return array<string, string>
     */
    public function hGetAll(string $key): array;

    /**
     * Définit la valeur d'un champ dans un Hash.
     */
    public function hSet(string $key, string $field, string $value): void;

    /**
     * Retourne la valeur d'un champ dans un Hash.
     * Retourne null si le champ ou la clé n'existe pas.
     */
    public function hGet(string $key, string $field): ?string;

    /**
     * Supprime un ou plusieurs champs d'un Hash.
     * Retourne le nombre de champs supprimés.
     */
    public function hDel(string $key, string ...$fields): int;

    // -------------------------------------------------------------------------
    // Clés génériques
    // -------------------------------------------------------------------------

    /**
     * Supprime une ou plusieurs clés.
     * Retourne le nombre de clés supprimées.
     */
    public function del(string ...$keys): int;

    // -------------------------------------------------------------------------
    // Scripts Lua
    // -------------------------------------------------------------------------

    /**
     * Exécute un script Lua côté serveur de façon atomique.
     *
     * @param string[] $keys   Clés Redis passées dans KEYS[]
     * @param mixed[]  $args   Arguments passés dans ARGV[]
     *
     * @return mixed Résultat retourné par le script Lua
     */
    public function eval(string $script, array $keys = [], array $args = []): mixed;

    // -------------------------------------------------------------------------
    // Connexion
    // -------------------------------------------------------------------------

    /**
     * Vérifie que la connexion Redis est active (PING).
     */
    public function ping(): bool;

    /**
     * Ferme proprement la connexion.
     */
    public function disconnect(): void;
}