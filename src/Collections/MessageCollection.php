<?php

namespace InnovativeSprouts\Semaphore\Collections;

use Illuminate\Support\Collection;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageData;
use InnovativeSprouts\Semaphore\Exceptions\ValidationException;

/**
 * @extends Collection<int, MessageData>
 */
class MessageCollection extends Collection
{
    /**
     * Semaphore returns a list for most calls, but may return a single message
     * object. Anything that is not a message (e.g. an error object sent with
     * HTTP 200) is rejected rather than turned into an empty MessageData.
     *
     * @param  array<mixed>  $payload
     */
    public static function fromResponse(array $payload): self
    {
        $items = array_is_list($payload) ? $payload : [$payload];

        foreach ($items as $item) {
            if (! is_array($item) || ! array_key_exists('message_id', $item)) {
                throw ValidationException::fromBody($payload);
            }
        }

        /** @var list<array<string, mixed>> $items */
        return new self(array_map(MessageData::fromArray(...), $items));
    }
}
