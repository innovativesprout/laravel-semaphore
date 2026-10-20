<?php

namespace InnovativeSprouts\Semaphore\Exceptions;

class MissingApiKeyException extends SemaphoreException
{
    public static function make(): self
    {
        return new self('No Semaphore API key configured. Set SEMAPHORE_API_KEY in your .env file.');
    }
}
