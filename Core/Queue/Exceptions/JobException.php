<?php

declare(strict_types=1);

namespace Core\Queue\Exceptions;

use Core\Queue\JobPayload;

class JobException extends QueueException
{
    public function __construct(
        string $message,
        private readonly JobPayload $payload,
        \Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getPayload(): JobPayload
    {
        return $this->payload;
    }
}