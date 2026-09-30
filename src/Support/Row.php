<?php

declare(strict_types=1);

namespace Rembon\LaravelAuditorPulse\Support;

/**
 * Typed reads from the stdClass rows Pulse aggregates return.
 *
 * @internal
 */
final class Row
{
    public static function string(mixed $row, string $column): string
    {
        $value = is_object($row) ? ($row->{$column} ?? null) : null;

        return is_scalar($value) ? (string) $value : '';
    }

    public static function int(mixed $row, string $column): int
    {
        $value = is_object($row) ? ($row->{$column} ?? null) : null;

        return is_numeric($value) ? (int) $value : 0;
    }
}
