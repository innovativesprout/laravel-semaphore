<?php

use InnovativeSprouts\Semaphore\Enums\Encoding;
use InnovativeSprouts\Semaphore\Enums\MessageType;
use InnovativeSprouts\Semaphore\ValueObjects\MessageBody;

it('rejects messages starting with TEST', fn () => MessageBody::from('test your luck'))
    ->throws(InvalidArgumentException::class);

it('rejects TEST after leading whitespace', fn () => MessageBody::from('  TEST'))
    ->throws(InvalidArgumentException::class);

it('allows TEST later or as part of a word', function () {
    expect(MessageBody::from('Testing 123')->value)->toBe('Testing 123')
        ->and(MessageBody::from('Run a TEST')->value)->toBe('Run a TEST');
});

it('rejects empty or whitespace messages', fn () => MessageBody::from('   '))
    ->throws(InvalidArgumentException::class);

it('detects GSM-7 encoding', fn () => expect(MessageBody::from('Hello @ £5 {ok}')->encoding())->toBe(Encoding::Gsm7));

it('counts GSM-7 segments', function (int $len, int $segments) {
    expect(MessageBody::from(str_repeat('a', $len))->segments())->toBe($segments);
})->with([[160, 1], [161, 2], [306, 2], [307, 3]]);

it('counts unicode segments', function (int $len, int $segments) {
    expect(MessageBody::from(str_repeat('ж', $len))->segments())->toBe($segments);
})->with([[70, 1], [71, 2], [134, 2], [135, 3]]);

it('treats emoji as unicode, two units each', function () {
    $b = MessageBody::from(str_repeat('😀', 35));

    expect($b->encoding())->toBe(Encoding::Unicode)
        ->and($b->length())->toBe(70)
        ->and($b->segments())->toBe(1);
});

it('counts GSM extension chars as two', fn () => expect(MessageBody::from(str_repeat('€', 80))->length())->toBe(160));

it('prices credits', function () {
    $b = MessageBody::from(str_repeat('a', 161));

    expect($b->credits(MessageType::Single))->toBe(2)
        ->and($b->credits(MessageType::Bulk))->toBe(2)
        ->and($b->credits(MessageType::Otp))->toBe(4)
        ->and($b->credits(MessageType::Priority))->toBe(4);
});
