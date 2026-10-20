<?php

namespace InnovativeSprouts\Semaphore\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InnovativeSprouts\Semaphore\Exceptions\AuthenticationException;
use InnovativeSprouts\Semaphore\Exceptions\MissingApiKeyException;
use InnovativeSprouts\Semaphore\Exceptions\RateLimitException;
use InnovativeSprouts\Semaphore\Exceptions\ServerException;
use InnovativeSprouts\Semaphore\Exceptions\ValidationException;
use JsonException;
use Throwable;

/**
 * The only class that talks HTTP to Semaphore.
 */
class SemaphoreConnector
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $defaultSender,
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly int $sendRetries,
        private readonly int $readRetries,
        private readonly int $sleepMs,
    ) {}

    /**
     * Send endpoints. Not retried unless asked: a retried timeout can send a duplicate SMS.
     *
     * @param  array<string, mixed>  $params
     * @return array<mixed>
     */
    public function post(string $path, array $params, ?int $retries = null): array
    {
        return $this->request('post', $path, $params, $retries ?? $this->sendRetries);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('get', $path, $query, $this->readRetries);
    }

    public function defaultSender(): ?string
    {
        return $this->defaultSender === '' ? null : $this->defaultSender;
    }

    /**
     * @param  'get'|'post'  $method
     * @param  array<string, mixed>  $data
     * @return array<mixed>
     */
    private function request(string $method, string $path, array $data, int $retries): array
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw MissingApiKeyException::make();
        }

        $data = ['apikey' => $this->apiKey] + $data;

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->retry(
                    max(1, $retries + 1),
                    $this->sleepMs,
                    fn (Throwable $e) => $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->serverError()),
                    throw: false,
                )
                ->{$method}($path, $data);
        } catch (ConnectionException $e) {
            throw ServerException::fromConnection($e);
        }

        return $this->decode($response);
    }

    /** @return array<mixed> */
    private function decode(Response $response): array
    {
        $status = $response->status();

        if ($status === 401 || $status === 403) {
            throw AuthenticationException::fromResponse($response);
        }

        if ($status === 429) {
            throw RateLimitException::fromResponse($response);
        }

        if ($response->clientError()) {
            $json = $response->json();

            throw ValidationException::fromBody(is_array($json) ? $json : $response->body(), $response);
        }

        if (! $response->successful()) {
            throw ServerException::fromResponse($response);
        }

        try {
            $json = json_decode($response->body(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ServerException::fromResponse($response, $e);
        }

        if (! is_array($json)) {
            throw ServerException::fromResponse($response);
        }

        return $json;
    }
}
