<?php

namespace InnovativeSprouts\Semaphore\Notifications;

use InnovativeSprouts\Semaphore\Enums\MessageType;

class SemaphoreMessage
{
    private MessageType $type = MessageType::Single;

    private ?string $sender = null;

    private ?string $code = null;

    final public function __construct(public string $content) {}

    public static function create(string $content): static
    {
        return new static($content);
    }

    public function sender(string $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    /** Send via the priority queue (2 credits per segment). Overrides otp(). */
    public function priority(): static
    {
        $this->type = MessageType::Priority;

        return $this;
    }

    /** Send via the OTP route; "{otp}" in the content is replaced. Overrides priority(). */
    public function otp(): static
    {
        $this->type = MessageType::Otp;

        return $this;
    }

    /** Use your own OTP code instead of a generated one. Implies otp(). */
    public function code(string $code): static
    {
        $this->code = $code;

        return $this->otp();
    }

    public function isPriority(): bool
    {
        return $this->type === MessageType::Priority;
    }

    public function isOtp(): bool
    {
        return $this->type === MessageType::Otp;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }
}
