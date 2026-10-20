<?php

namespace InnovativeSprouts\Semaphore\DataTransferObjects\Concerns;

use Carbon\CarbonImmutable;

/**
 * Defensive readers for Semaphore payloads, whose fields are loosely typed.
 */
trait ReadsPayload
{
    /** @param array<string, mixed> $payload */
    private static function int(array $payload, string $key): ?int
    {
        return is_numeric($payload[$key] ?? null) ? (int) $payload[$key] : null;
    }

    /** @param array<string, mixed> $payload */
    private static function string(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    /** @param array<string, mixed> $payload */
    private static function date(array $payload, string $key): ?CarbonImmutable
    {
        $value = self::string($payload, $key);

        return $value === null ? null : CarbonImmutable::parse($value);
    }
}
