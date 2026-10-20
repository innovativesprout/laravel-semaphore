<?php

namespace InnovativeSprouts\Semaphore\DataTransferObjects;

use InnovativeSprouts\Semaphore\DataTransferObjects\Concerns\ReadsPayload;

final readonly class AccountData
{
    use ReadsPayload;

    public function __construct(
        public int $accountId,
        public string $accountName,
        public string $status,
        public int $creditBalance,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            accountId: self::int($payload, 'account_id') ?? 0,
            accountName: self::string($payload, 'account_name') ?? '',
            status: self::string($payload, 'status') ?? '',
            creditBalance: self::int($payload, 'credit_balance') ?? 0,
        );
    }
}
