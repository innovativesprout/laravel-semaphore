<?php

namespace InnovativeSprouts\Semaphore\Actions;

use Illuminate\Support\Collection;
use InnovativeSprouts\Semaphore\Actions\Concerns\ReadsPages;
use InnovativeSprouts\Semaphore\DataTransferObjects\TransactionData;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;

class GetTransactionsAction
{
    use ReadsPages;

    public function __construct(private readonly SemaphoreConnector $connector) {}

    /** @return Collection<int, TransactionData> */
    public function execute(int $page = 1, int $limit = 100): Collection
    {
        return $this->page('account/transactions', $page, $limit, TransactionData::fromArray(...));
    }
}
