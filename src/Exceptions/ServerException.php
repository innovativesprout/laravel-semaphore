<?php

namespace InnovativeSprouts\Semaphore\Exceptions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Throwable;

class ServerException extends SemaphoreException
{
    public static function fromResponse(Response $response, ?Throwable $previous = null): self
    {
        return new self("Semaphore returned an unexpected response (HTTP {$response->status()}).", $response, $previous);
    }

    /**
     * Guzzle 7 puts the full request URI (with ?apikey=...) in its message, so the
     * key is redacted and the original exception is not chained: logs print the chain.
     */
    public static function fromConnection(ConnectionException $e): self
    {
        $message = preg_replace('/apikey=[^&\s]*/i', 'apikey=***', $e->getMessage()) ?? 'Connection failed.';

        return new self("Could not reach Semaphore: {$message}");
    }
}
