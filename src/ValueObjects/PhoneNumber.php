<?php

namespace InnovativeSprouts\Semaphore\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class PhoneNumber implements Stringable
{
    private function __construct(public string $value) {}

    public static function from(string $number): self
    {
        $value = preg_replace('/[\s-]/', '', $number) ?? '';

        if (! preg_match('/^(09\d{9}|639\d{9}|\+639\d{9})$/', $value)) {
            throw new InvalidArgumentException(
                "Invalid Philippine mobile number [{$number}]. Expected 09XXXXXXXXX, 639XXXXXXXXX or +639XXXXXXXXX."
            );
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
