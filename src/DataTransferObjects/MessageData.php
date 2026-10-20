<?php

namespace InnovativeSprouts\Semaphore\DataTransferObjects;

use Carbon\CarbonImmutable;
use InnovativeSprouts\Semaphore\DataTransferObjects\Concerns\ReadsPayload;
use InnovativeSprouts\Semaphore\Enums\MessageStatus;
use InnovativeSprouts\Semaphore\Enums\MessageType;
use InnovativeSprouts\Semaphore\Enums\Network;

final readonly class MessageData
{
    use ReadsPayload;

    public function __construct(
        public int $id,
        public ?int $userId,
        public ?string $user,
        public ?int $accountId,
        public ?string $account,
        public string $recipient,
        public string $message,
        public ?string $code,
        public ?string $senderName,
        public ?Network $network,
        public ?string $rawNetwork,
        public ?MessageStatus $status,
        public ?string $rawStatus,
        public ?MessageType $type,
        public ?string $rawType,
        public ?string $source,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $network = self::string($payload, 'network');
        $status = self::string($payload, 'status');
        $type = self::string($payload, 'type');

        return new self(
            id: self::int($payload, 'message_id') ?? 0,
            userId: self::int($payload, 'user_id'),
            user: self::string($payload, 'user'),
            accountId: self::int($payload, 'account_id'),
            account: self::string($payload, 'account'),
            recipient: self::string($payload, 'recipient') ?? '',
            message: self::string($payload, 'message') ?? '',
            code: self::string($payload, 'code'),
            senderName: self::string($payload, 'sender_name'),
            network: Network::tryFromInsensitive($network),
            rawNetwork: $network,
            status: MessageStatus::tryFromInsensitive($status),
            rawStatus: $status,
            type: MessageType::tryFromInsensitive($type),
            rawType: $type,
            source: self::string($payload, 'source'),
            createdAt: self::date($payload, 'created_at'),
            updatedAt: self::date($payload, 'updated_at'),
        );
    }
}
