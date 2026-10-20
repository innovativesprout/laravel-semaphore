<?php

use InnovativeSprouts\Semaphore\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

/** @return list<string> distinct valid PH mobile numbers */
function numbers(int $count): array
{
    return array_map(fn (int $i) => '0917'.str_pad((string) $i, 7, '0', STR_PAD_LEFT), range(1, $count));
}

/** @return array<mixed> decoded JSON from tests/Fixtures */
function apiFixture(string $name): array
{
    return json_decode((string) file_get_contents(__DIR__."/Fixtures/{$name}.json"), true, flags: JSON_THROW_ON_ERROR);
}
