<?php

namespace InnovativeSprouts\Semaphore\Actions;

use InnovativeSprouts\Semaphore\DataTransferObjects\AccountData;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;

class GetAccountAction
{
    public function __construct(private readonly SemaphoreConnector $connector) {}

    public function execute(): AccountData
    {
        return AccountData::fromArray($this->connector->get('account'));
    }
}
