<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\DataTransferObjects\AccountData;
use InnovativeSprouts\Semaphore\Exceptions\ServerException;
use InnovativeSprouts\Semaphore\Facades\Semaphore;

beforeEach(fn () => Http::preventStrayRequests());

it('sends through the facade', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    expect(Semaphore::send('09171234567', 'Hi'))->toBeInstanceOf(MessageCollection::class);
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/messages') && $r['number'] === '09171234567');
});

it('sends to many numbers with a sender name', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    Semaphore::send(['09171234567', '09181234567'], 'Hi', sender: 'SHOP');

    Http::assertSent(fn (Request $r) => $r['number'] === '09171234567,09181234567' && $r['sendername'] === 'SHOP');
});

it('sends priority messages', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    Semaphore::priority('09171234567', 'Server down');

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/priority'));
});

it('sends an OTP and returns the code', function () {
    Http::fake(['*' => Http::response(apiFixture('otp'))]);

    expect(Semaphore::otp('09171234567', 'Your code is {otp}')->first()->code)->toBe('332200');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/otp') && $r['message'] === 'Your code is {otp}');
});

it('uses the default OTP message', function () {
    Http::fake(['*' => Http::response(apiFixture('otp'))]);

    Semaphore::otp('09171234567');

    Http::assertSent(fn (Request $r) => $r['message'] === 'Your One Time Password is {otp}.');
});

it('rejects invalid input before any request', function () {
    expect(fn () => Semaphore::send([], 'Hi'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Semaphore::send('09171234567', '  '))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('filters messages', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    Semaphore::messages(status: 'Sent', startDate: '2026-10-01');

    Http::assertSent(fn (Request $r) => $r['status'] === 'sent' && $r['startDate'] === '2026-10-01');
});

it('rejects bad message filter dates before any request', function () {
    expect(fn () => Semaphore::messages(startDate: '10/01/2026'))->toThrow(InvalidArgumentException::class, 'Y-m-d');

    Http::assertNothingSent();
});

it('reads the account', function () {
    Http::fake(['*' => Http::response(apiFixture('account'))]);

    expect(Semaphore::account())->toBeInstanceOf(AccountData::class);
});

it('reads message, transactions, sender names and users', function () {
    Http::fake([
        '*/messages/*' => Http::response(apiFixture('messages')[0]),
        '*/account/transactions*' => Http::response(apiFixture('transactions')),
        '*/account/sendernames*' => Http::response(apiFixture('sendernames')),
        '*/account/users*' => Http::response(apiFixture('users')),
    ]);

    expect(Semaphore::message(12345)->id)->toBe(12345)
        ->and(Semaphore::transactions())->toHaveCount(1)
        ->and(Semaphore::senderNames())->toHaveCount(1)
        ->and(Semaphore::users(page: 2, limit: 10))->toHaveCount(1);
});

it('applies a retry override to one call only', function () {
    Http::fake(['*' => Http::sequence()
        ->push('', 500)->push('', 500)->push(apiFixture('messages'))
        ->push('', 500)]);

    Semaphore::retry(2)->send('09171234567', 'Hi');
    Http::assertSentCount(3);

    expect(fn () => Semaphore::send('09171234567', 'Hi'))->toThrow(ServerException::class);
    Http::assertSentCount(4);
});
