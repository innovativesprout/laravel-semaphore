<?php

namespace InnovativeSprouts\Semaphore\DataTransferObjects;

use InnovativeSprouts\Semaphore\DataTransferObjects\Concerns\ReadsPayload;

final readonly class UserData
{
    use ReadsPayload;

    public function __construct(
        public int $userId,
        public string $email,
        public string $role,
        public string $status,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            userId: self::int($payload, 'user_id') ?? 0,
            email: self::string($payload, 'email') ?? '',
            role: self::string($payload, 'role') ?? '',
            status: self::string($payload, 'status') ?? '',
        );
    }
}
