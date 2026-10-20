<?php

use InnovativeSprouts\Semaphore\Enums\MessageType;
use InnovativeSprouts\Semaphore\ValueObjects\MessageBody;
use InnovativeSprouts\Semaphore\ValueObjects\Recipients;

it('chunks recipients by 1000', function (int $count, array $sizes) {
    $chunks = Recipients::from(numbers($count))->chunks();

    expect(array_map(fn (Recipients $r) => $r->count(), $chunks))->toBe($sizes);
})->with([
    [1000, [1000]],
    [1001, [1000, 1]],
    [2500, [1000, 1000, 500]],
]);

it('splits comma-joined strings and drops empty entries', function () {
    expect(Recipients::from('09171234567, 09181234567,,')->count())->toBe(2);
});

it('removes duplicate numbers', function () {
    expect(Recipients::from(['09171234567', '09171234567'])->count())->toBe(1);
});

it('rejects an empty array', fn () => Recipients::from([]))->throws(InvalidArgumentException::class);

it('rejects an empty string', fn () => Recipients::from(' , '))->throws(InvalidArgumentException::class);

it('joins numbers for the API parameter', function () {
    expect(Recipients::from(['09171234567', '09181234567'])->toParameter())->toBe('09171234567,09181234567');
});

it('rejects an invalid number in the list', fn () => Recipients::from(['09171234567', '123']))
    ->throws(InvalidArgumentException::class);

it('multiplies credits by the recipient count', function () {
    $body = MessageBody::from(str_repeat('a', 161));

    expect(Recipients::from(numbers(3))->credits($body, MessageType::Priority))->toBe(12);
});

it('dedupes the same number written in different formats', function () {
    $recipients = Recipients::from(['09171234567', '639171234567', '+639171234567', '0917-123-4567']);

    expect($recipients->count())->toBe(1)
        ->and($recipients->toParameter())->toBe('09171234567');
});
