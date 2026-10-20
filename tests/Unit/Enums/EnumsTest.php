<?php

use InnovativeSprouts\Semaphore\Enums\MessageStatus;
use InnovativeSprouts\Semaphore\Enums\MessageType;
use InnovativeSprouts\Semaphore\Enums\Network;

it('maps API values case-insensitively', function () {
    expect(MessageStatus::tryFromInsensitive('Pending'))->toBe(MessageStatus::Pending)
        ->and(MessageType::tryFromInsensitive('Single'))->toBe(MessageType::Single)
        ->and(Network::tryFromInsensitive('Globe'))->toBe(Network::Globe);
});

it('returns null for unknown or missing values', function () {
    expect(MessageStatus::tryFromInsensitive('Delivered'))->toBeNull()
        ->and(MessageStatus::tryFromInsensitive(null))->toBeNull();
});
