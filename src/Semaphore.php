<?php

namespace InnovativeSprouts\Semaphore;

use Illuminate\Support\Collection;
use InnovativeSprouts\Semaphore\Actions\GetAccountAction;
use InnovativeSprouts\Semaphore\Actions\GetMessageAction;
use InnovativeSprouts\Semaphore\Actions\GetMessagesAction;
use InnovativeSprouts\Semaphore\Actions\GetSenderNamesAction;
use InnovativeSprouts\Semaphore\Actions\GetTransactionsAction;
use InnovativeSprouts\Semaphore\Actions\GetUsersAction;
use InnovativeSprouts\Semaphore\Actions\SendMessageAction;
use InnovativeSprouts\Semaphore\Actions\SendOtpAction;
use InnovativeSprouts\Semaphore\Actions\SendPriorityMessageAction;
use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\DataTransferObjects\AccountData;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageData;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageFilterData;
use InnovativeSprouts\Semaphore\DataTransferObjects\SenderNameData;
use InnovativeSprouts\Semaphore\DataTransferObjects\TransactionData;
use InnovativeSprouts\Semaphore\DataTransferObjects\UserData;
use InnovativeSprouts\Semaphore\ValueObjects\MessageBody;
use InnovativeSprouts\Semaphore\ValueObjects\PhoneNumber;
use InnovativeSprouts\Semaphore\ValueObjects\Recipients;
use InnovativeSprouts\Semaphore\ValueObjects\SenderName;

/**
 * Entry point behind the Semaphore facade: wraps raw input in value objects
 * and delegates to the actions.
 */
class Semaphore
{
    private ?int $retries = null;

    public function __construct(
        protected readonly SendMessageAction $sendMessage,
        protected readonly SendPriorityMessageAction $sendPriority,
        protected readonly SendOtpAction $sendOtp,
        protected readonly GetMessagesAction $getMessages,
        protected readonly GetMessageAction $getMessage,
        protected readonly GetAccountAction $getAccount,
        protected readonly GetTransactionsAction $getTransactions,
        protected readonly GetSenderNamesAction $getSenderNames,
        protected readonly GetUsersAction $getUsers,
    ) {}

    /**
     * Retry the next send on connection errors and 5xx. Beware: a retried
     * timeout can deliver a duplicate SMS and charge twice.
     */
    public function retry(int $times): static
    {
        $clone = clone $this;
        $clone->retries = $times;

        return $clone;
    }

    /** @param string|array<int, string> $numbers */
    public function send(string|array $numbers, string $message, ?string $sender = null): MessageCollection
    {
        return $this->sendMessage->execute(
            Recipients::from($numbers), MessageBody::from($message), self::sender($sender), $this->retries,
        );
    }

    /** @param string|array<int, string> $numbers */
    public function priority(string|array $numbers, string $message, ?string $sender = null): MessageCollection
    {
        return $this->sendPriority->execute(
            Recipients::from($numbers), MessageBody::from($message), self::sender($sender), $this->retries,
        );
    }

    public function otp(
        string $number,
        string $message = 'Your One Time Password is {otp}.',
        ?string $code = null,
        ?string $sender = null,
    ): MessageCollection {
        return $this->sendOtp->execute(
            PhoneNumber::from($number), MessageBody::from($message), $code, self::sender($sender), $this->retries,
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
        return $this->getMessages->execute(new MessageFilterData($page, $limit, $startDate, $endDate, $network, $status));
    }

    public function message(int $id): MessageData
    {
        return $this->getMessage->execute($id);
    }

    public function account(): AccountData
    {
        return $this->getAccount->execute();
    }

    /** @return Collection<int, TransactionData> */
    public function transactions(int $page = 1, int $limit = 100): Collection
    {
        return $this->getTransactions->execute($page, $limit);
    }

    /** @return Collection<int, SenderNameData> */
    public function senderNames(int $page = 1, int $limit = 100): Collection
    {
        return $this->getSenderNames->execute($page, $limit);
    }

    /** @return Collection<int, UserData> */
    public function users(int $page = 1, int $limit = 100): Collection
    {
        return $this->getUsers->execute($page, $limit);
    }

    private static function sender(?string $sender): ?SenderName
    {
        return $sender === null ? null : SenderName::from($sender);
    }
}
