<?php

namespace InnovativeSprouts\Semaphore\ValueObjects;

use Countable;
use InnovativeSprouts\Semaphore\Enums\MessageType;
use InvalidArgumentException;

final readonly class Recipients implements Countable
{
    /** Semaphore accepts at most this many numbers per API call. */
    public const MAX_PER_REQUEST = 1000;

    /** @param list<PhoneNumber> $numbers */
    private function __construct(private array $numbers) {}

    /** @param string|array<int, string> $numbers comma-separated string or list */
    public static function from(string|array $numbers): self
    {
        $raw = is_array($numbers) ? implode(',', $numbers) : $numbers;

        $unique = [];

        foreach (explode(',', $raw) as $number) {
            if (trim($number) === '') {
                continue;
            }

            $phone = PhoneNumber::from($number);
            // 09…, 639… and +639… are the same phone: key on the last 10 digits, keep the first format.
            $unique[substr($phone->value, -10)] ??= $phone;
        }

        if ($unique === []) {
            throw new InvalidArgumentException('At least one recipient number is required.');
        }

        return new self(array_values($unique));
    }

    /** @return list<PhoneNumber> */
    public function all(): array
    {
        return $this->numbers;
    }

    public function count(): int
    {
        return count($this->numbers);
    }

    /**
     * @param  positive-int  $size
     * @return list<self>
     */
    public function chunks(int $size = self::MAX_PER_REQUEST): array
    {
        return array_map(fn (array $chunk) => new self($chunk), array_chunk($this->numbers, $size));
    }

    public function toParameter(): string
    {
        return implode(',', $this->numbers);
    }

    /** Total credits to send $body to every recipient. */
    public function credits(MessageBody $body, MessageType $type): int
    {
        return $this->count() * $body->credits($type);
    }
}
