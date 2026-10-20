<?php

use InnovativeSprouts\Semaphore\DataTransferObjects\AccountData;
use InnovativeSprouts\Semaphore\DataTransferObjects\SenderNameData;
use InnovativeSprouts\Semaphore\DataTransferObjects\TransactionData;
use InnovativeSprouts\Semaphore\DataTransferObjects\UserData;

it('maps the account', function () {
    $account = AccountData::fromArray(apiFixture('account'));

    expect($account->accountId)->toBe(987654)
        ->and($account->accountName)->toBe('My Account')
        ->and($account->status)->toBe('Active')
        ->and($account->creditBalance)->toBe(1500);
});

it('maps a transaction and keeps the raw payload', function () {
    $payload = apiFixture('transactions')[0];
    $transaction = TransactionData::fromArray($payload);

    expect($transaction->accountId)->toBe(987654)
        ->and($transaction->creditBalance)->toBe(1500)
        ->and($transaction->raw)->toBe($payload);
});

it('maps a sender name', function () {
    $sender = SenderNameData::fromArray(apiFixture('sendernames')[0]);

    expect($sender->name)->toBe('MYSHOP')
        ->and($sender->status)->toBe('Active')
        ->and($sender->createdAt?->toDateString())->toBe('2024-05-01');
});

it('maps a user', function () {
    $user = UserData::fromArray(apiFixture('users')[0]);

    expect($user->userId)->toBe(54321)
        ->and($user->email)->toBe('timmy@toolbox.com')
        ->and($user->role)->toBe('Owner')
        ->and($user->status)->toBe('Active');
});
