<?php

namespace InnovativeSprouts\Semaphore\Actions;

use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageFilterData;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;

class GetMessagesAction
{
    public function __construct(private readonly SemaphoreConnector $connector) {}

    public function execute(MessageFilterData $filter): MessageCollection
    {
        return MessageCollection::fromResponse($this->connector->get('messages', $filter->toQuery()));
    }
}
