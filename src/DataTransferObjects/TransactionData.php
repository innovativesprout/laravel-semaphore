<?php

namespace InnovativeSprouts\Semaphore\DataTransferObjects;

use InnovativeSprouts\Semaphore\DataTransferObjects\Concerns\ReadsPayload;

/**
 * Semaphore documents transactions with the same fields as the account,
 * so the full payload is kept on $raw for anything else it returns.
 */
final readonly class TransactionData
{
    use ReadsPayload;

    /** @param array<string, mixed> $raw */
    public function __construct(
        public ?int $accountId,
        public ?string $accountName,
        public ?string $status,
        public ?int $creditBalance,
        public array $raw,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            accountId: self::int($payload, 'account_id'),
            accountName: self::string($payload, 'account_name'),
            status: self::string($payload, 'status'),
            creditBalance: self::int($payload, 'credit_balance'),
            raw: $payload,
        );
    }
}
