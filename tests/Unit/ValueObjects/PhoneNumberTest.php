<?php

use InnovativeSprouts\Semaphore\ValueObjects\PhoneNumber;
use InnovativeSprouts\Semaphore\ValueObjects\SenderName;

it('accepts valid PH mobile numbers', function (string $input, string $stored) {
    expect(PhoneNumber::from($input)->value)->toBe($stored)
        ->and((string) PhoneNumber::from($input))->toBe($stored);
})->with([
    ['09171234567', '09171234567'],
    ['639171234567', '639171234567'],
    ['+639171234567', '+639171234567'],
    ['0917-123-4567', '09171234567'],
    [' 0917 123 4567 ', '09171234567'],
]);

it('rejects invalid numbers', fn (string $input) => PhoneNumber::from($input))
    ->throws(InvalidArgumentException::class)
    ->with(['12345', '0917123456', '091712345678', '']);

it('rejects an empty sender name', fn () => SenderName::from('  '))->throws(InvalidArgumentException::class);

it('trims the sender name', fn () => expect(SenderName::from(' MYSHOP ')->value)->toBe('MYSHOP'));
