<?php

namespace InnovativeSprouts\Semaphore\Actions\Concerns;

use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\Exceptions\SemaphoreException;
use InnovativeSprouts\Semaphore\Exceptions\ValidationException;
use InnovativeSprouts\Semaphore\ValueObjects\MessageBody;
use InnovativeSprouts\Semaphore\ValueObjects\Recipients;
use InnovativeSprouts\Semaphore\ValueObjects\SenderName;

trait SendsMessages
{
    /**
     * Sends in chunks of 1000 recipients. On failure, the exception carries
     * the messages from chunks Semaphore already accepted.
     *
     * @param  array<string, string>  $extra
     */
    private function sendInChunks(
        string $path,
        Recipients $to,
        MessageBody $body,
        ?SenderName $sender,
        ?int $retries,
        array $extra = [],
    ): MessageCollection {
        $sent = new MessageCollection;
        $senderName = $sender !== null ? $sender->value : $this->connector->defaultSender();

        foreach ($to->chunks() as $chunk) {
            $params = array_filter([
                'number' => $chunk->toParameter(),
                'message' => $body->value,
                'sendername' => $senderName,
            ], fn (?string $value) => $value !== null) + $extra;

            try {
                $sent = $sent->concat(self::messagesFrom($this->connector->post($path, $params, $retries)));
            } catch (SemaphoreException $e) {
                $e->sentMessages = $sent;

                throw $e;
            }
        }

        return $sent;
    }

    /**
     * Sends must answer with a non-empty list of messages; anything else is an
     * error Semaphore sent with 200.
     *
     * @param  array<mixed>  $response
     */
    private static function messagesFrom(array $response): MessageCollection
    {
        if ($response === [] || ! array_is_list($response)) {
            throw ValidationException::fromBody($response);
        }

        return MessageCollection::fromResponse($response);
    }
}
