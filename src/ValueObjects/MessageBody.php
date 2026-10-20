<?php

namespace InnovativeSprouts\Semaphore\ValueObjects;

use InnovativeSprouts\Semaphore\Enums\Encoding;
use InnovativeSprouts\Semaphore\Enums\MessageType;
use InvalidArgumentException;
use Stringable;

final readonly class MessageBody implements Stringable
{
    /** GSM 03.38 basic character set. */
    private const GSM_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?"
        .'¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';

    /** GSM 03.38 extension table: each character costs two units. */
    private const GSM_EXTENSION = "^{}\\[~]|€\f";

    private function __construct(public string $value) {}

    public static function from(string $message): self
    {
        if (trim($message) === '') {
            throw new InvalidArgumentException('Message cannot be empty.');
        }

        // Semaphore silently drops messages that start with the word "TEST".
        if (preg_match('/^\s*test\b/i', $message)) {
            throw new InvalidArgumentException(
                'Semaphore silently ignores messages that start with the word "TEST". Reword the message.'
            );
        }

        return new self($message);
    }

    public function encoding(): Encoding
    {
        foreach (mb_str_split($this->value) as $char) {
            if (! str_contains(self::GSM_BASIC, $char) && ! str_contains(self::GSM_EXTENSION, $char)) {
                return Encoding::Unicode;
            }
        }

        return Encoding::Gsm7;
    }

    /** Length in encoding units: GSM-7 septets, or UTF-16 code units for Unicode. */
    public function length(): int
    {
        if ($this->encoding() === Encoding::Unicode) {
            return intdiv(strlen(mb_convert_encoding($this->value, 'UTF-16BE', 'UTF-8')), 2);
        }

        $length = 0;

        foreach (mb_str_split($this->value) as $char) {
            $length += str_contains(self::GSM_EXTENSION, $char) ? 2 : 1;
        }

        return $length;
    }

    public function segments(): int
    {
        [$single, $multi] = $this->encoding() === Encoding::Gsm7 ? [160, 153] : [70, 67];

        $length = $this->length();

        return $length <= $single ? 1 : (int) ceil($length / $multi);
    }

    /** Credits this message costs per recipient. */
    public function credits(MessageType $type): int
    {
        return $this->segments() * $type->creditsPerSegment();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
