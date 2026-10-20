<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InnovativeSprouts\Semaphore\Actions\GetAccountAction;
use InnovativeSprouts\Semaphore\Actions\GetMessageAction;
use InnovativeSprouts\Semaphore\Actions\GetMessagesAction;
use InnovativeSprouts\Semaphore\Actions\GetSenderNamesAction;
use InnovativeSprouts\Semaphore\Actions\GetTransactionsAction;
use InnovativeSprouts\Semaphore\Actions\GetUsersAction;
use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\DataTransferObjects\AccountData;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageData;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageFilterData;
use InnovativeSprouts\Semaphore\DataTransferObjects\SenderNameData;
use InnovativeSprouts\Semaphore\DataTransferObjects\TransactionData;
use InnovativeSprouts\Semaphore\DataTransferObjects\UserData;
use InnovativeSprouts\Semaphore\Exceptions\ValidationException;

beforeEach(fn () => Http::preventStrayRequests());

it('lists messages with filters', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    $messages = app(GetMessagesAction::class)->execute(
        new MessageFilterData(page: 2, startDate: '2026-10-01', status: 'Sent'),
    );

    expect($messages)->toBeInstanceOf(MessageCollection::class)->toHaveCount(1);
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/messages?')
        && $r['page'] === 2 && $r['limit'] === 100
        && $r['startDate'] === '2026-10-01' && $r['status'] === 'sent');
});

it('gets one message from an object body', function () {
    Http::fake(['*' => Http::response(apiFixture('messages')[0])]);

    $message = app(GetMessageAction::class)->execute(12345);

    expect($message)->toBeInstanceOf(MessageData::class)->and($message->id)->toBe(12345);
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/messages/12345?'));
});

it('gets one message from a one-item list body', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    expect(app(GetMessageAction::class)->execute(12345)->id)->toBe(12345);
});

it('gets the account', function () {
    Http::fake(['*' => Http::response(apiFixture('account'))]);

    expect(app(GetAccountAction::class)->execute())->toBeInstanceOf(AccountData::class)
        ->creditBalance->toBe(1500);
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/account?'));
});

it('lists transactions, sender names and users', function (string $action, string $path, string $fixture, string $dto) {
    Http::fake(['*' => Http::response(apiFixture($fixture))]);

    $items = app($action)->execute(page: 3, limit: 50);

    expect($items)->toHaveCount(1)->first()->toBeInstanceOf($dto);
    Http::assertSent(fn (Request $r) => str_contains($r->url(), "/{$path}?") && $r['page'] === 3 && $r['limit'] === 50);
})->with([
    [GetTransactionsAction::class, 'account/transactions', 'transactions', TransactionData::class],
    [GetSenderNamesAction::class, 'account/sendernames', 'sendernames', SenderNameData::class],
    [GetUsersAction::class, 'account/users', 'users', UserData::class],
]);

it('rejects a 2xx error object instead of a message', function () {
    Http::fake(['*' => Http::response(['message' => 'Not found'])]);

    app(GetMessageAction::class)->execute(999);
})->throws(ValidationException::class, 'Not found');

it('rejects a 2xx error object when listing messages', function () {
    Http::fake(['*' => Http::response(['message' => 'Invalid filter'])]);

    app(GetMessagesAction::class)->execute(new MessageFilterData);
})->throws(ValidationException::class);

it('returns an empty collection for an empty message list', function () {
    Http::fake(['*' => Http::response([])]);

    expect(app(GetMessagesAction::class)->execute(new MessageFilterData))->toBeEmpty();
});

it('rejects a 2xx error object on paginated account endpoints', function () {
    Http::fake(['*' => Http::response(['error' => 'Something went wrong'])]);

    app(GetUsersAction::class)->execute();
})->throws(ValidationException::class);
