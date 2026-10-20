<?php

namespace InnovativeSprouts\Semaphore\Actions;

use Illuminate\Support\Collection;
use InnovativeSprouts\Semaphore\Actions\Concerns\ReadsPages;
use InnovativeSprouts\Semaphore\DataTransferObjects\UserData;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;

class GetUsersAction
{
    use ReadsPages;

    public function __construct(private readonly SemaphoreConnector $connector) {}

    /** @return Collection<int, UserData> */
    public function execute(int $page = 1, int $limit = 100): Collection
    {
        return $this->page('account/users', $page, $limit, UserData::fromArray(...));
    }
}
