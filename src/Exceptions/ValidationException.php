<?php

namespace InnovativeSprouts\Semaphore\Exceptions;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

class ValidationException extends SemaphoreException
{
    /** @var array<string, list<string>> */
    private array $errors;

    /**
     * Semaphore does not document its error shape, so anything that is not
     * {field: [messages]} or {field: "message"} is kept as the raw body.
     *
     * @param  array<mixed>|string  $body
     */
    public static function fromBody(array|string $body, ?Response $response = null): self
    {
        $errors = [];

        if (is_array($body) && ! array_is_list($body)) {
            foreach ($body as $field => $messages) {
                $messages = is_array($messages) ? $messages : [$messages];
                $errors[(string) $field] = array_values(array_map(
                    fn (mixed $m) => is_scalar($m) ? (string) $m : (string) json_encode($m),
                    $messages,
                ));
            }
        }

        if ($errors === []) {
            $errors = ['body' => [is_string($body) ? $body : (string) json_encode($body)]];
        }

        $exception = new self('Semaphore rejected the request: '.Str::limit(collect($errors)->flatten()->implode(' '), 500), $response);
        $exception->errors = $errors;

        return $exception;
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
