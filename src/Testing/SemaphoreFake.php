<?php

namespace InnovativeSprouts\Semaphore\Testing;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\DataTransferObjects\AccountData;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageData;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageFilterData;
use InnovativeSprouts\Semaphore\Enums\MessageStatus;
use InnovativeSprouts\Semaphore\Enums\MessageType;
use InnovativeSprouts\Semaphore\Exceptions\ValidationException;
use InnovativeSprouts\Semaphore\Semaphore;
use InnovativeSprouts\Semaphore\ValueObjects\MessageBody;
use InnovativeSprouts\Semaphore\ValueObjects\PhoneNumber;
use InnovativeSprouts\Semaphore\ValueObjects\Recipients;
use InnovativeSprouts\Semaphore\ValueObjects\SenderName;
use PHPUnit\Framework\Assert;

/**
 * Records sends instead of calling Semaphore. Input is validated exactly as
 * in production, so tests catch the same mistakes.
 */
class SemaphoreFake extends Semaphore
{
    /** @var list<array{type: MessageType, message: MessageData}> */
    private array $sent = [];

    // Deliberately skips the parent constructor: the fake never calls the actions.
    public function __construct() {}

    public function retry(int $times): static
    {
        return $this;
    }

    public function send(string|array $numbers, string $message, ?string $sender = null): MessageCollection
    {
        return $this->record(MessageType::Single, Recipients::from($numbers), MessageBody::from($message), $sender);
    }

    public function priority(string|array $numbers, string $message, ?string $sender = null): MessageCollection
    {
        return $this->record(MessageType::Priority, Recipients::from($numbers), MessageBody::from($message), $sender);
    }

    public function otp(
        string $number,
        string $message = 'Your One Time Password is {otp}.',
        ?string $code = null,
        ?string $sender = null,
    ): MessageCollection {
        $code ??= str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $body = MessageBody::from($message);

        // Mirrors Semaphore: replace {otp}, or append the code when there is no placeholder.
        $text = str_contains($body->value, '{otp}')
            ? str_replace('{otp}', $code, $body->value)
            : "{$body->value} Your One Time Password is {$code}";

        return $this->record(
            MessageType::Otp,
            Recipients::from(PhoneNumber::from($number)->value),
            MessageBody::from($text),
            $sender,
            $code,
        );
    }

    public function messages(
        int $page = 1,
        int $limit = 100,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $network = null,
        ?string $status = null,
    ): MessageCollection {
        new MessageFilterData($page, $limit, $startDate, $endDate, $network, $status);

        // Faked messages have no network, so any network filter matches nothing.
        return $this->sentMessages()
            ->filter(fn (MessageData $m) => $network === null
                && ($status === null || strtolower($status) === $m->status?->value)
                && ($startDate === null || $m->createdAt?->toDateString() >= $startDate)
                && ($endDate === null || $m->createdAt?->toDateString() <= $endDate))
            ->forPage($page, $limit)
            ->values();
    }

    public function message(int $id): MessageData
    {
        return $this->sentMessages()->firstWhere('id', $id)
            ?? throw ValidationException::fromBody(['id' => ["No faked message with id {$id}."]]);
    }

    public function account(): AccountData
    {
        return new AccountData(accountId: 0, accountName: 'Semaphore Fake', status: 'Active', creditBalance: 0);
    }

    public function transactions(int $page = 1, int $limit = 100): Collection
    {
        return collect();
    }

    public function senderNames(int $page = 1, int $limit = 100): Collection
    {
        return collect();
    }

    public function users(int $page = 1, int $limit = 100): Collection
    {
        return collect();
    }

    /** @param (callable(MessageData): bool)|null $callback */
    public function assertSent(?callable $callback = null): void
    {
        Assert::assertTrue($this->matching(null, $callback)->isNotEmpty(), 'No matching SMS was sent.');
    }

    public function assertSentTo(string $number): void
    {
        $number = PhoneNumber::from($number)->value;

        Assert::assertTrue(
            $this->sentMessages()->contains(fn (MessageData $m) => $m->recipient === $number),
            "No SMS was sent to [{$number}].",
        );
    }

    public function assertSentCount(int $count): void
    {
        Assert::assertCount($count, $this->sent, "Expected {$count} SMS, sent ".count($this->sent).'.');
    }

    /** @param (callable(MessageData): bool)|null $callback */
    public function assertPrioritySent(?callable $callback = null): void
    {
        Assert::assertTrue($this->matching(MessageType::Priority, $callback)->isNotEmpty(), 'No matching priority SMS was sent.');
    }

    /** @param (callable(MessageData): bool)|null $callback */
    public function assertOtpSent(?callable $callback = null): void
    {
        Assert::assertTrue($this->matching(MessageType::Otp, $callback)->isNotEmpty(), 'No matching OTP was sent.');
    }

    public function assertNothingSent(): void
    {
        Assert::assertEmpty($this->sent, count($this->sent).' SMS were sent unexpectedly.');
    }

    private function record(MessageType $type, Recipients $to, MessageBody $body, ?string $sender, ?string $code = null): MessageCollection
    {
        $senderName = $sender === null ? (config('semaphore.sender_name') ?: null) : SenderName::from($sender)->value;
        $sent = new MessageCollection;

        foreach ($to->all() as $number) {
            $message = new MessageData(
                id: count($this->sent) + 1,
                userId: null,
                user: null,
                accountId: null,
                account: null,
                recipient: $number->value,
                message: $body->value,
                code: $code,
                senderName: $senderName,
                network: null,
                rawNetwork: null,
                status: MessageStatus::Queued,
                rawStatus: 'Queued',
                type: $type,
                rawType: $type->value,
                source: 'Api',
                createdAt: CarbonImmutable::now(),
                updatedAt: CarbonImmutable::now(),
            );

            $this->sent[] = ['type' => $type, 'message' => $message];
            $sent->push($message);
        }

        return $sent;
    }

    private function sentMessages(): MessageCollection
    {
        return new MessageCollection(array_column($this->sent, 'message'));
    }

    /** @param (callable(MessageData): bool)|null $callback */
    private function matching(?MessageType $type, ?callable $callback): MessageCollection
    {
        return new MessageCollection(array_values(array_map(
            fn (array $entry) => $entry['message'],
            array_filter($this->sent, fn (array $entry) => ($type === null || $entry['type'] === $type)
                && ($callback === null || $callback($entry['message']))),
        )));
    }
}
