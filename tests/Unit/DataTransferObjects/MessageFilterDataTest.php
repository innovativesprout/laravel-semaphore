<?php

use InnovativeSprouts\Semaphore\DataTransferObjects\MessageFilterData;

it('builds the default query', fn () => expect((new MessageFilterData)->toQuery())->toBe(['page' => 1, 'limit' => 100]));

it('lowercases status and network and drops nulls', function () {
    expect((new MessageFilterData(status: 'Sent', network: 'Globe'))->toQuery())
        ->toBe(['page' => 1, 'limit' => 100, 'network' => 'globe', 'status' => 'sent']);
});

it('uses the camelCase date keys the API expects', function () {
    expect((new MessageFilterData(page: 2, startDate: '2026-10-01', endDate: '2026-10-31'))->toQuery())
        ->toBe(['page' => 2, 'limit' => 100, 'startDate' => '2026-10-01', 'endDate' => '2026-10-31']);
});

it('rejects dates not in Y-m-d', fn () => new MessageFilterData(startDate: '10/01/2026'))
    ->throws(InvalidArgumentException::class, 'Y-m-d');

it('rejects impossible dates', fn () => new MessageFilterData(endDate: '2026-02-30'))
    ->throws(InvalidArgumentException::class, 'Y-m-d');

it('rejects a limit above 1000', fn () => new MessageFilterData(limit: 1001))->throws(InvalidArgumentException::class);

it('rejects a limit below 1', fn () => new MessageFilterData(limit: 0))->throws(InvalidArgumentException::class);

it('rejects a page below 1', fn () => new MessageFilterData(page: 0))->throws(InvalidArgumentException::class);
