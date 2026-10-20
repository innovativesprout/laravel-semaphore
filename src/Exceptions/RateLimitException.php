<?php

namespace InnovativeSprouts\Semaphore\Exceptions;

use Illuminate\Http\Client\Response;

class RateLimitException extends SemaphoreException
{
    public function __construct(string $message, ?Response $response, public readonly ?int $retryAfter)
    {
        parent::__construct($message, $response);
    }

    public static function fromResponse(Response $response): self
    {
        $header = $response->header('Retry-After');
        $retryAfter = is_numeric($header) ? (int) $header : null;

        return new self(
            'Semaphore rate limit reached'.($retryAfter === null ? '.' : ", retry after {$retryAfter} seconds."),
            $response,
            $retryAfter,
        );
    }
}
