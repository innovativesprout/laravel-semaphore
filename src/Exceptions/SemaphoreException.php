<?php

namespace InnovativeSprouts\Semaphore\Exceptions;

use Illuminate\Http\Client\Response;
use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use RuntimeException;
use Throwable;

abstract class SemaphoreException extends RuntimeException
{
    /**
     * Messages already accepted by Semaphore before this failure (chunked bulk sends).
     */
    public MessageCollection $sentMessages;

    public function __construct(string $message, public readonly ?Response $response = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $response?->status() ?? 0, $previous);

        $this->sentMessages = new MessageCollection;
    }
}
