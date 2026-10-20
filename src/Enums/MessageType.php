<?php

namespace InnovativeSprouts\Semaphore\Enums;

use InnovativeSprouts\Semaphore\Enums\Concerns\MapsApiValues;

enum MessageType: string
{
    use MapsApiValues;

    case Single = 'single';
    case Bulk = 'bulk';
    case Priority = 'priority';
    case Otp = 'otp';

    /** Credits charged per 160-character segment. */
    public function creditsPerSegment(): int
    {
        return match ($this) {
            self::Priority, self::Otp => 2,
            self::Single, self::Bulk => 1,
        };
    }
}
