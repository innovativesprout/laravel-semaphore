<?php

namespace InnovativeSprouts\Semaphore\Enums\Concerns;

/**
 * Semaphore returns enum-like values in mixed case ("Single", "single").
 */
trait MapsApiValues
{
    public static function tryFromInsensitive(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom(strtolower(trim($value)));
    }
}
