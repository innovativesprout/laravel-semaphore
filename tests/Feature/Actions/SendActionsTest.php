<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InnovativeSprouts\Semaphore\Actions\SendMessageAction;
use InnovativeSprouts\Semaphore\Actions\SendOtpAction;
use InnovativeSprouts\Semaphore\Actions\SendPriorityMessageAction;
use InnovativeSprouts\Semaphore\Exceptions\ServerException;
use InnovativeSprouts\Semaphore\Exceptions\ValidationException;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;
use InnovativeSprouts\Semaphore\ValueObjects\MessageBody;
use InnovativeSprouts\Semaphore\ValueObjects\PhoneNumber;
use InnovativeSprouts\Semaphore\ValueObjects\Recipients;
use InnovativeSprouts\Semaphore\ValueObjects\SenderName;

beforeEach(fn () => Http::preventStrayRequests());

/** Echo one Semaphore message per recipient in the request. */
function echoMessages(Request $request): array
{
    return array_map(fn (string $number, int $i) => [
        'message_id' => $i + 1,
        'recipient' => $number,
        'message' => $request['message'],
        'code' => $request['code'] ?? null,
        'status' => 'Queued',
        'type' => 'Single',
    ], explode(',', $request['number']), array_keys(explode(',', $request['number'])));
}

it('sends a message', function () {
    Http::fake(fn (Request $r) => Http::response(echoMessages($r)));

    $sent = app(SendMessageAction::class)->execute(Recipients::from('09171234567'), MessageBody::from('Hi'));

    expect($sent)->toHaveCount(1)->and($sent->first()->recipient)->toBe('09171234567');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/messages') && $r['message'] === 'Hi');
});

it('splits more than 1000 recipients into chunks', function () {
    Http::fake(fn (Request $r) => Http::response(echoMessages($r)));

    $sent = app(SendMessageAction::class)->execute(Recipients::from(numbers(2500)), MessageBody::from('Sale!'));

    expect($sent)->toHaveCount(2500);
    Http::assertSentCount(3);

    $sizes = Http::recorded()->map(fn (array $pair) => count(explode(',', $pair[0]['number'])))->all();
    expect($sizes)->toBe([1000, 1000, 500]);
});

it('reports already-sent chunks when a later chunk fails', function () {
    Http::fake(['*' => Http::sequence()
        ->push(array_map(fn ($n) => ['message_id' => 1, 'recipient' => $n, 'message' => 'x'], numbers(1000)))
        ->push('', 500)]);

    try {
        app(SendMessageAction::class)->execute(Recipients::from(numbers(2500)), MessageBody::from('Sale!'));
        $this->fail('Expected ServerException');
    } catch (ServerException $e) {
        expect($e->sentMessages)->toHaveCount(1000);
    }

    Http::assertSentCount(2);
});

it('sends priority messages to the priority endpoint', function () {
    Http::fake(fn (Request $r) => Http::response(echoMessages($r)));

    app(SendPriorityMessageAction::class)->execute(Recipients::from('09171234567'), MessageBody::from('Server down'));

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/priority'));
});

it('sends an OTP with a custom code', function () {
    Http::fake(fn (Request $r) => Http::response(echoMessages($r)));

    $sent = app(SendOtpAction::class)->execute(PhoneNumber::from('09171234567'), MessageBody::from('Code {otp}'), code: '482913');

    expect($sent->first()->code)->toBe('482913');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/otp') && $r['code'] === '482913');
});

it('omits code when Semaphore should generate it', function () {
    Http::fake(fn (Request $r) => Http::response(echoMessages($r)));

    app(SendOtpAction::class)->execute(PhoneNumber::from('09171234567'), MessageBody::from('Code {otp}'));

    Http::assertSent(fn (Request $r) => ! array_key_exists('code', $r->data()));
});

it('uses the configured sender name by default', function () {
    config(['semaphore.sender_name' => 'MYAPP']);
    app()->forgetInstance(SemaphoreConnector::class);
    Http::fake(fn (Request $r) => Http::response(echoMessages($r)));

    app(SendMessageAction::class)->execute(Recipients::from('09171234567'), MessageBody::from('Hi'));

    Http::assertSent(fn (Request $r) => $r['sendername'] === 'MYAPP');
});

it('prefers an explicit sender name', function () {
    config(['semaphore.sender_name' => 'MYAPP']);
    app()->forgetInstance(SemaphoreConnector::class);
    Http::fake(fn (Request $r) => Http::response(echoMessages($r)));

    app(SendMessageAction::class)->execute(Recipients::from('09171234567'), MessageBody::from('Hi'), SenderName::from('SHOP'));

    Http::assertSent(fn (Request $r) => $r['sendername'] === 'SHOP');
});

it('omits sendername when none is configured', function () {
    Http::fake(fn (Request $r) => Http::response(echoMessages($r)));

    app(SendMessageAction::class)->execute(Recipients::from('09171234567'), MessageBody::from('Hi'));

    Http::assertSent(fn (Request $r) => ! array_key_exists('sendername', $r->data()));
});

it('treats a 200 field-error body as a validation error', function () {
    Http::fake(['*' => Http::response(['number' => ['The number format is invalid.']])]);

    app(SendMessageAction::class)->execute(Recipients::from('09171234567'), MessageBody::from('Hi'));
})->throws(ValidationException::class, 'The number format is invalid.');

it('passes the retry override to the connector', function () {
    Http::fake(['*' => Http::sequence()->push('', 500)->push(apiFixture('messages'))]);

    app(SendMessageAction::class)->execute(Recipients::from('09171234567'), MessageBody::from('Hi'), retries: 1);

    Http::assertSentCount(2);
});
