<?php

namespace InnovativeSprouts\Semaphore\Actions;

use InnovativeSprouts\Semaphore\Actions\Concerns\SendsMessages;
use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;
use InnovativeSprouts\Semaphore\ValueObjects\MessageBody;
use InnovativeSprouts\Semaphore\ValueObjects\Recipients;
use InnovativeSprouts\Semaphore\ValueObjects\SenderName;

class SendMessageAction
{
    use SendsMessages;

    public function __construct(private readonly SemaphoreConnector $connector) {}

    public function execute(Recipients $to, MessageBody $body, ?SenderName $sender = null, ?int $retries = null): MessageCollection
    {
        return $this->sendInChunks('messages', $to, $body, $sender, $retries);
    }
}
