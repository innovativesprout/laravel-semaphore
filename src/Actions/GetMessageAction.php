<?php

namespace InnovativeSprouts\Semaphore\Actions;

use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageData;
use InnovativeSprouts\Semaphore\Exceptions\ValidationException;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;

class GetMessageAction
{
    public function __construct(private readonly SemaphoreConnector $connector) {}

    public function execute(int $id): MessageData
    {
        $response = $this->connector->get("messages/{$id}");

        return MessageCollection::fromResponse($response)->first()
            ?? throw ValidationException::fromBody($response);
    }
}
