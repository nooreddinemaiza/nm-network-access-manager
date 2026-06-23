<?php

declare(strict_types=1);

namespace Core\Queue\Exceptions;

class InvalidDriverException extends QueueException
{
    public static function forDriver(string $driver): self
    {
        return new self(
            sprintf('Queue driver "%s" is not registered or not supported.', $driver)
        );
    }
}