<?php

namespace InnovativeSprouts\Semaphore\Actions;

use InnovativeSprouts\Semaphore\Actions\Concerns\SendsMessages;
use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;
use InnovativeSprouts\Semaphore\ValueObjects\MessageBody;
use InnovativeSprouts\Semaphore\ValueObjects\PhoneNumber;
use InnovativeSprouts\Semaphore\ValueObjects\Recipients;
use InnovativeSprouts\Semaphore\ValueObjects\SenderName;

/**
 * Sends via Semaphore's dedicated OTP route. Semaphore replaces "{otp}" in the
 * message with $code, or with a generated code when $code is null.
 */
class SendOtpAction
{
    use SendsMessages;

    public function __construct(private readonly SemaphoreConnector $connector) {}

    public function execute(
        PhoneNumber $to,
        MessageBody $body,
        ?string $code = null,
        ?SenderName $sender = null,
        ?int $retries = null,
    ): MessageCollection {
        $extra = $code === null ? [] : ['code' => $code];

        return $this->sendInChunks('otp', Recipients::from($to->value), $body, $sender, $retries, $extra);
    }
}
