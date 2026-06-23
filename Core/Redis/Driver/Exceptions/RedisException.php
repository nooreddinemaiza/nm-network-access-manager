<?php

declare(strict_types=1);

namespace Core\Redis\Exceptions;

class RedisException extends \RuntimeException
{
    public static function connectionFailed(string $host, int $port, string $reason): self
    {
        return new self(sprintf(
            'Redis connection failed to %s:%d — %s',
            $host,
            $port,
            $reason,
        ));
    }

    public static function commandFailed(string $command, string $reason): self
    {
        return new self(sprintf(
            'Redis command "%s" failed — %s',
            $command,
            $reason,
        ));
    }

    public static function scriptFailed(string $reason): self
    {
        return new self('Redis Lua script execution failed — ' . $reason);
    }
}