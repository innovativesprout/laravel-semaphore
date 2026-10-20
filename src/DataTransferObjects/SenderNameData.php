<?php

namespace InnovativeSprouts\Semaphore\DataTransferObjects;

use Carbon\CarbonImmutable;
use InnovativeSprouts\Semaphore\DataTransferObjects\Concerns\ReadsPayload;

final readonly class SenderNameData
{
    use ReadsPayload;

    public function __construct(
        public string $name,
        public string $status,
        public ?CarbonImmutable $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            name: self::string($payload, 'name') ?? '',
            status: self::string($payload, 'status') ?? '',
            createdAt: self::date($payload, 'created_at'),
        );
    }
}
