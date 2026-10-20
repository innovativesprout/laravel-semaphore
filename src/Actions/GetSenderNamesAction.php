<?php

namespace InnovativeSprouts\Semaphore\Actions;

use Illuminate\Support\Collection;
use InnovativeSprouts\Semaphore\Actions\Concerns\ReadsPages;
use InnovativeSprouts\Semaphore\DataTransferObjects\SenderNameData;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;

class GetSenderNamesAction
{
    use ReadsPages;

    public function __construct(private readonly SemaphoreConnector $connector) {}

    /** @return Collection<int, SenderNameData> */
    public function execute(int $page = 1, int $limit = 100): Collection
    {
        return $this->page('account/sendernames', $page, $limit, SenderNameData::fromArray(...));
    }
}
