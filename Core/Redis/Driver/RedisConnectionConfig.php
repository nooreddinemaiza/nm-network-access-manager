<?php

declare(strict_types=1);

namespace Core\Redis\Driver;

/**
 * Configuration immuable d'une connexion Redis.
 *
 * Usage :
 *   $config = new RedisConnectionConfig(host: '127.0.0.1', password: 'secret');
 *   $config = RedisConnectionConfig::fromArray($configArray);
 */
final readonly class RedisConnectionConfig
{
    public function __construct(
        public string  $host            = '127.0.0.1',
        public int     $port            = 6379,
        public int     $database        = 0,
        public ?string $password        = null,
        public ?string $username        = null,   // Redis ACL (Redis 6+)
        public float   $timeout         = 2.0,    // Timeout de connexion en secondes
        public float   $readTimeout     = 2.0,    // Timeout de lecture en secondes
        public bool    $persistent      = false,  // Connexion persistante (CLI/Worker)
        public ?string $persistentId    = null,   // Identifiant de connexion persistante
        public ?string $tlsCertFile     = null,   // Certificat TLS (optionnel)
        public ?string $tlsKeyFile      = null,
        public ?string $tlsCaFile       = null,
        public string  $prefix          = '',     // Préfixe global de toutes les clés
    ) {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException(
                sprintf('Invalid Redis port: %d. Must be between 1 and 65535.', $port)
            );
        }

        if ($database < 0) {
            throw new \InvalidArgumentException('Redis database index must be >= 0.');
        }

        if ($timeout <= 0) {
            throw new \InvalidArgumentException('Redis timeout must be a positive float.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            host:         (string)  ($config['host']          ?? '127.0.0.1'),
            port:         (int)     ($config['port']          ?? 6379),
            database:     (int)     ($config['database']      ?? 0),
            password:     isset($config['password'])   ? (string) $config['password']   : null,
            username:     isset($config['username'])   ? (string) $config['username']   : null,
            timeout:      (float)   ($config['timeout']       ?? 2.0),
            readTimeout:  (float)   ($config['read_timeout']  ?? 2.0),
            persistent:   (bool)    ($config['persistent']    ?? false),
            persistentId: isset($config['persistent_id']) ? (string) $config['persistent_id'] : null,
            tlsCertFile:  isset($config['tls_cert'])   ? (string) $config['tls_cert']   : null,
            tlsKeyFile:   isset($config['tls_key'])    ? (string) $config['tls_key']    : null,
            tlsCaFile:    isset($config['tls_ca'])     ? (string) $config['tls_ca']     : null,
            prefix:       (string)  ($config['prefix']        ?? ''),
        );
    }

    public function hasTls(): bool
    {
        return $this->tlsCertFile !== null || $this->tlsCaFile !== null;
    }

    public function hasAuth(): bool
    {
        return $this->password !== null;
    }
}