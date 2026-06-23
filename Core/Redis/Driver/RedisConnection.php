<?php

declare(strict_types=1);

namespace Core\Redis\Driver;

use Core\Redis\Contracts\RedisConnectionInterface;
use Core\Redis\Exceptions\RedisException;

/**
 * Implémentation de RedisConnectionInterface via l'extension PHP ext-redis.
 *
 * Gère :
 *  - Connexion simple et persistante
 *  - Authentification (password seul, ou username+password pour Redis ACL 6+)
 *  - TLS
 *  - Sélection de la base de données
 *  - Préfixe global des clés
 *  - Reconnexion automatique en cas de perte de connexion
 */
final class RedisConnection implements RedisConnectionInterface
{
    private \Redis $client;
    private bool   $connected = false;

    public function __construct(
        private readonly RedisConnectionConfig $config,
    ) {
        if (!extension_loaded('redis')) {
            throw new \LogicException(
                'The "redis" PHP extension is required to use RedisConnection. '
                . 'Install it via: pecl install redis'
            );
        }
    }

    // -------------------------------------------------------------------------
    // Listes
    // -------------------------------------------------------------------------

    public function lPush(string $key, string ...$values): int
    {
        return (int) $this->run('lPush', $key, ...$values);
    }

    public function rPopLPush(string $source, string $destination): ?string
    {
        $result = $this->run('rPopLPush', $source, $destination);

        return $result === false ? null : (string) $result;
    }

    public function lRem(string $key, string $value, int $count = 1): int
    {
        return (int) $this->run('lRem', $key, $value, $count);
    }

    public function lRange(string $key, int $start, int $stop): array
    {
        $result = $this->run('lRange', $key, $start, $stop);

        return is_array($result) ? $result : [];
    }

    public function lLen(string $key): int
    {
        return (int) $this->run('lLen', $key);
    }

    // -------------------------------------------------------------------------
    // Sorted Sets
    // -------------------------------------------------------------------------

    public function zAdd(string $key, float $score, string $member): int
    {
        return (int) $this->run('zAdd', $key, $score, $member);
    }

    public function zRangeByScore(string $key, string $min, string $max): array
    {
        $result = $this->run('zRangeByScore', $key, $min, $max);

        return is_array($result) ? $result : [];
    }

    public function zRem(string $key, string ...$members): int
    {
        return (int) $this->run('zRem', $key, ...$members);
    }

    // -------------------------------------------------------------------------
    // Hashes
    // -------------------------------------------------------------------------

    public function hMSet(string $key, array $fields): void
    {
        $this->run('hMSet', $key, $fields);
    }

    public function hGetAll(string $key): array
    {
        $result = $this->run('hGetAll', $key);

        return is_array($result) ? $result : [];
    }

    public function hSet(string $key, string $field, string $value): void
    {
        $this->run('hSet', $key, $field, $value);
    }

    public function hGet(string $key, string $field): ?string
    {
        $result = $this->run('hGet', $key, $field);

        return ($result === false || $result === null) ? null : (string) $result;
    }

    public function hDel(string $key, string ...$fields): int
    {
        return (int) $this->run('hDel', $key, ...$fields);
    }

    // -------------------------------------------------------------------------
    // Clés génériques
    // -------------------------------------------------------------------------

    public function del(string ...$keys): int
    {
        return (int) $this->run('del', ...$keys);
    }

    // -------------------------------------------------------------------------
    // Scripts Lua
    // -------------------------------------------------------------------------

    public function eval(string $script, array $keys = [], array $args = []): mixed
    {
        $numKeys = count($keys);
        $params  = array_merge($keys, array_map('strval', $args));

        try {
            $result = $this->client()->eval($script, $params, $numKeys);
        } catch (\RedisException $e) {
            throw RedisException::scriptFailed($e->getMessage());
        }

        if ($result === false) {
            $lastError = $this->client->getLastError();
            $this->client->clearLastError();

            if ($lastError !== null) {
                throw RedisException::scriptFailed($lastError);
            }
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Connexion
    // -------------------------------------------------------------------------

    public function ping(): bool
    {
        try {
            $response = $this->client()->ping();

            // ext-redis retourne true ou "+PONG" selon la version
            return $response === true || $response === '+PONG' || $response === 'PONG';
        } catch (\Throwable) {
            return false;
        }
    }

    public function disconnect(): void
    {
        if ($this->connected) {
            try {
                $this->client->close();
            } catch (\Throwable) {
                // Connexion déjà fermée — on ignore.
            }
            $this->connected = false;
        }
    }

    // -------------------------------------------------------------------------
    // Connexion lazy + reconnexion automatique
    // -------------------------------------------------------------------------

    private function client(): \Redis
    {
        if (!$this->connected) {
            $this->connect();
        }

        return $this->client;
    }

    /**
     * @throws RedisException
     */
    private function connect(): void
    {
        $this->client = new \Redis();

        $cfg = $this->config;

        try {
            $connectMethod = $cfg->persistent ? 'pconnect' : 'connect';

            $args = [
                $this->buildHost($cfg),
                $cfg->port,
                $cfg->timeout,
            ];

            if ($cfg->persistent && $cfg->persistentId !== null) {
                $args[] = $cfg->persistentId;
            }

            $result = $this->client->{$connectMethod}(...$args);

            if ($result === false) {
                throw RedisException::connectionFailed(
                    $cfg->host,
                    $cfg->port,
                    'connect() returned false'
                );
            }

            // Authentification Redis ACL (username + password) ou legacy (password seul)
            if ($cfg->hasAuth()) {
                $auth = $cfg->username !== null
                    ? [$cfg->username, $cfg->password]
                    : $cfg->password;

                if ($this->client->auth($auth) === false) {
                    throw RedisException::connectionFailed(
                        $cfg->host,
                        $cfg->port,
                        'Authentication failed'
                    );
                }
            }

            // Sélection de la base de données
            if ($cfg->database !== 0) {
                $this->client->select($cfg->database);
            }

            // Timeout de lecture
            $this->client->setOption(\Redis::OPT_READ_TIMEOUT, (string) $cfg->readTimeout);

            // Sérialisation : on gère nous-mêmes la sérialisation (JSON),
            // donc on désactive la sérialisation native de ext-redis.
            $this->client->setOption(\Redis::OPT_SERIALIZER, (string) \Redis::SERIALIZER_NONE);

            // Préfixe global des clés
            if ($cfg->prefix !== '') {
                $this->client->setOption(\Redis::OPT_PREFIX, $cfg->prefix);
            }

            $this->connected = true;
        } catch (RedisException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw RedisException::connectionFailed($cfg->host, $cfg->port, $e->getMessage());
        }
    }

    /**
     * Construit le DSN host en ajoutant le schéma TLS si nécessaire.
     */
    private function buildHost(RedisConnectionConfig $cfg): string
    {
        if ($cfg->hasTls()) {
            // ext-redis supporte les sockets TLS via le schéma tls://
            return 'tls://' . $cfg->host;
        }

        return $cfg->host;
    }

    /**
     * Exécute une commande Redis avec reconnexion automatique en cas d'erreur.
     *
     * @throws RedisException
     */
    private function run(string $command, mixed ...$args): mixed
    {
        try {
            $result = $this->client()->{$command}(...$args);

            // Vérifie si une erreur Redis a été enregistrée (ex: WRONGTYPE)
            $lastError = $this->client->getLastError();
            if ($lastError !== null) {
                $this->client->clearLastError();
                throw RedisException::commandFailed($command, $lastError);
            }

            return $result;
        } catch (RedisException $e) {
            throw $e;
        } catch (\RedisException $e) {
            // Tentative de reconnexion unique sur erreur de connexion
            $this->connected = false;

            try {
                $result = $this->client()->{$command}(...$args);
                return $result;
            } catch (\Throwable $retryException) {
                throw RedisException::commandFailed(
                    $command,
                    'Reconnect failed: ' . $retryException->getMessage()
                );
            }
        } catch (\Throwable $e) {
            throw RedisException::commandFailed($command, $e->getMessage());
        }
    }
}