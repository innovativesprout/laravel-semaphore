<?php

namespace InnovativeSprouts\Semaphore\Exceptions;

use Illuminate\Http\Client\Response;

class AuthenticationException extends SemaphoreException
{
    public static function fromResponse(Response $response): self
    {
        return new self("Semaphore rejected the API key (HTTP {$response->status()}). Check SEMAPHORE_API_KEY.", $response);
    }
}
