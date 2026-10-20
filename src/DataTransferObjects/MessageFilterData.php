<?php

namespace InnovativeSprouts\Semaphore\DataTransferObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class MessageFilterData
{
    public function __construct(
        public int $page = 1,
        public int $limit = 100,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $network = null,
        public ?string $status = null,
    ) {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be 1 or greater.');
        }

        if ($limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('Limit must be between 1 and 1000.');
        }

        foreach (['startDate' => $startDate, 'endDate' => $endDate] as $name => $date) {
            $parsed = $date === null ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $date);

            if ($date !== null && ($parsed === false || $parsed?->format('Y-m-d') !== $date)) {
                throw new InvalidArgumentException("The {$name} [{$date}] must be a valid date in Y-m-d format.");
            }
        }
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        return array_filter([
            'page' => $this->page,
            'limit' => $this->limit,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'network' => $this->network === null ? null : strtolower($this->network),
            'status' => $this->status === null ? null : strtolower($this->status),
        ], fn ($value) => $value !== null);
    }
}
