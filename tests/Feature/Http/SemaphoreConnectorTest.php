<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InnovativeSprouts\Semaphore\Exceptions\AuthenticationException;
use InnovativeSprouts\Semaphore\Exceptions\MissingApiKeyException;
use InnovativeSprouts\Semaphore\Exceptions\RateLimitException;
use InnovativeSprouts\Semaphore\Exceptions\ServerException;
use InnovativeSprouts\Semaphore\Exceptions\ValidationException;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;

beforeEach(fn () => Http::preventStrayRequests());

function connector(): SemaphoreConnector
{
    return app(SemaphoreConnector::class);
}

it('posts form-encoded data with the api key', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    $data = connector()->post('messages', ['number' => '09171234567', 'message' => 'Hi']);

    expect($data)->toBe(apiFixture('messages'));
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://api.semaphore.co/api/v4/messages'
        && $request->isForm()
        && $request['apikey'] === 'test-key'
        && $request['number'] === '09171234567'
        && $request['message'] === 'Hi');
});

it('sends the api key in the query on GET', function () {
    Http::fake(['*' => Http::response(apiFixture('account'))]);

    connector()->get('account');

    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://api.semaphore.co/api/v4/account?')
        && $request['apikey'] === 'test-key');
});

it('maps 401 to AuthenticationException', function () {
    Http::fake(['*' => Http::response(['message' => 'Unauthorized'], 401)]);

    connector()->get('account');
})->throws(AuthenticationException::class);

it('maps 422 to ValidationException with field errors', function () {
    Http::fake(['*' => Http::response(['number' => ['The number format is invalid.']], 422)]);

    try {
        connector()->post('messages', []);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['number'][0])->toBe('The number format is invalid.')
            ->and($e->response?->status())->toBe(422);
    }
});

it('normalises single-string field errors', function () {
    Http::fake(['*' => Http::response(['sendername' => 'Unknown sender name.'], 400)]);

    try {
        connector()->post('messages', []);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors())->toBe(['sendername' => ['Unknown sender name.']]);
    }
});

it('keeps the raw body when the error shape is unknown', function () {
    Http::fake(['*' => Http::response('Bad request', 400)]);

    try {
        connector()->post('messages', []);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors())->toBe(['body' => ['Bad request']]);
    }
});

it('maps 429 to RateLimitException without retrying', function () {
    Http::fake(['*' => Http::response([], 429, ['Retry-After' => '40'])]);

    try {
        connector()->post('messages', [], retries: 2);
        $this->fail('Expected RateLimitException');
    } catch (RateLimitException $e) {
        expect($e->retryAfter)->toBe(40);
    }

    Http::assertSentCount(1);
});

it('does not retry sends by default', function () {
    Http::fake(['*' => Http::sequence()->push('', 500)->push(apiFixture('messages'))]);

    expect(fn () => connector()->post('messages', []))->toThrow(ServerException::class);
    Http::assertSentCount(1);
});

it('retries sends on 5xx when asked', function () {
    Http::fake(['*' => Http::sequence()->push('', 500)->push('', 502)->push(apiFixture('messages'))]);

    expect(connector()->post('messages', [], retries: 2))->toBe(apiFixture('messages'));
    Http::assertSentCount(3);
});

it('uses the configured send retries', function () {
    config(['semaphore.retry.sends' => 1]);
    app()->forgetInstance(SemaphoreConnector::class);
    Http::fake(['*' => Http::sequence()->push('', 500)->push(apiFixture('messages'))]);

    expect(connector()->post('messages', []))->toBe(apiFixture('messages'));
    Http::assertSentCount(2);
});

it('never retries 4xx', function () {
    Http::fake(['*' => Http::response(['number' => ['invalid']], 422)]);

    expect(fn () => connector()->post('messages', [], retries: 2))->toThrow(ValidationException::class);
    Http::assertSentCount(1);
});

it('retries reads on 5xx', function () {
    Http::fake(['*' => Http::sequence()->push('', 500)->push('', 500)->push(apiFixture('account'))]);

    expect(connector()->get('account'))->toBe(apiFixture('account'));
    Http::assertSentCount(3);
});

it('maps connection failures to ServerException', function () {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    connector()->get('account');
})->throws(ServerException::class, 'Connection timed out');

it('maps a non-JSON success body to ServerException', function () {
    Http::fake(['*' => Http::response('<html>oops</html>', 200)]);

    connector()->get('account');
})->throws(ServerException::class);

it('maps 5xx to ServerException', function () {
    Http::fake(['*' => Http::response('<html>down</html>', 503)]);

    connector()->get('account');
})->throws(ServerException::class);

it('requires an api key at request time, not at boot', function () {
    config(['semaphore.api_key' => null]);
    app()->forgetInstance(SemaphoreConnector::class);

    $connector = connector();

    expect(fn () => $connector->get('account'))->toThrow(MissingApiKeyException::class);
    Http::assertNothingSent();
});

it('never leaks the api key from connection errors', function () {
    Http::fake(fn () => throw new ConnectionException(
        'cURL error 28: Operation timed out for https://api.semaphore.co/api/v4/account?apikey=test-key&page=1'
    ));

    try {
        connector()->get('account');
        $this->fail('Expected ServerException');
    } catch (ServerException $e) {
        expect((string) $e)->not->toContain('test-key')
            ->and($e->getMessage())->toContain('apikey=***');
    }
});

it('truncates long raw bodies in the message but keeps them in errors', function () {
    $html = '<html>'.str_repeat('x', 5000).'</html>';
    Http::fake(['*' => Http::response($html, 400)]);

    try {
        connector()->post('messages', []);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect(strlen($e->getMessage()))->toBeLessThanOrEqual(600)
            ->and($e->errors())->toBe(['body' => [$html]]);
    }
});
