<?php

use Carbon\CarbonImmutable;
use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageData;
use InnovativeSprouts\Semaphore\Enums\MessageStatus;
use InnovativeSprouts\Semaphore\Enums\MessageType;
use InnovativeSprouts\Semaphore\Enums\Network;

it('maps an OTP response', function () {
    $message = MessageData::fromArray(apiFixture('otp')[0]);

    expect($message->id)->toBe(12345)
        ->and($message->userId)->toBe(54321)
        ->and($message->recipient)->toBe('639998887777')
        ->and($message->code)->toBe('332200')
        ->and($message->senderName)->toBe('MySenderName')
        ->and($message->network)->toBe(Network::Globe)
        ->and($message->status)->toBe(MessageStatus::Pending)
        ->and($message->rawStatus)->toBe('Pending')
        ->and($message->type)->toBe(MessageType::Single)
        ->and($message->source)->toBe('Api')
        ->and($message->createdAt)->toEqual(CarbonImmutable::parse('2020-01-01 01:01:01'));
});

it('has no code for regular messages', fn () => expect(MessageData::fromArray(apiFixture('messages')[0])->code)->toBeNull());

it('keeps unknown statuses as raw strings', function () {
    $message = MessageData::fromArray(['status' => 'Delivered'] + apiFixture('messages')[0]);

    expect($message->status)->toBeNull()->and($message->rawStatus)->toBe('Delivered');
});

it('tolerates missing optional fields', function () {
    $message = MessageData::fromArray(['message_id' => 1, 'recipient' => '09171234567', 'message' => 'Hi']);

    expect($message->status)->toBeNull()
        ->and($message->network)->toBeNull()
        ->and($message->createdAt)->toBeNull();
});

it('builds a collection from a list', fn () => expect(MessageCollection::fromResponse(apiFixture('messages')))
    ->toHaveCount(1)
    ->first()->toBeInstanceOf(MessageData::class));

it('builds a collection from a single object', fn () => expect(MessageCollection::fromResponse(apiFixture('messages')[0]))
    ->toHaveCount(1));
