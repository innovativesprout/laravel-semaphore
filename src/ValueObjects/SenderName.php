<?php

namespace InnovativeSprouts\Semaphore\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class SenderName implements Stringable
{
    private function __construct(public string $value) {}

    // Semaphore validates registered sender names itself; we only reject blanks.
    public static function from(string $name): self
    {
        $value = trim($name);

        if ($value === '') {
            throw new InvalidArgumentException('Sender name cannot be empty.');
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
