<?php

namespace App\Support\Excel;

/**
 * Sanitizes user-controlled strings before writing them into an
 * Excel/CSV cell.
 *
 * Excel treats values starting with =, +, -, @, tab, or CR as
 * formulas. When a user exports a driver named "=HYPERLINK(...)"
 * and someone opens the file, the formula executes.
 *
 * This helper prefixes any suspicious value with a single quote,
 * which Excel interprets as "treat the rest as literal text".
 */
final class SafeCell
{
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public static function sanitize(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if ($value === '') {
            return $value;
        }

        // Strip control characters that can split cells or inject headers.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        $firstChar = $value[0] ?? '';

        if (in_array($firstChar, self::DANGEROUS_PREFIXES, true)) {
            return "'".$value;
        }

        return $value;
    }
}
