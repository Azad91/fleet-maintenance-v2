<?php

namespace App\Enums;

/**
 * All valid values for `complaints.yer` (English: "location").
 *
 * The column name `yer` was intentionally kept in the schema during
 * the AZ→EN migration for backwards compatibility — see migration
 * 2026_09_04_000000_rename_az_columns_to_en.php, which explicitly
 * skips this column. The enum is named in English to match future
 * intentions.
 *
 * NOTE: Like ComplaintStatus and ComplaintType, the model attribute
 * is NOT cast to this enum yet. Views and accessors still compare
 * raw strings, and the cast will be introduced in D1d together with
 * the corresponding view updates.
 */
enum Location: string
{
    case Road   = 'road';
    case Garage = 'garage';

    /**
     * Human-readable label, translated via lang/{locale}/enums.php.
     */
    public function label(): string
    {
        return __('enums.location.' . $this->value);
    }

    /**
     * Leading emoji for compact UI rendering.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Road   => '🛣️',
            self::Garage => '🏠',
        };
    }

    /**
     * True when the complaint was reported while the bus was on the road.
     * On-road complaints require driver information and a reported time.
     */
    public function isRoad(): bool
    {
        return $this === self::Road;
    }

    /**
     * True when the complaint was reported at the garage.
     * Garage complaints do not require driver information.
     */
    public function isGarage(): bool
    {
        return $this === self::Garage;
    }

    /**
     * True when the location requires a driver to be attached.
     *
     * Semantically identical to isRoad(), but expressed in terms of
     * the business rule it represents. Use this in services that
     * decide whether to require driver_id / driver_name.
     */
    public function requiresDriver(): bool
    {
        return $this === self::Road;
    }

    /**
     * True when the location requires a reported date/time.
     *
     * On-road reports are always timestamped at the moment of the
     * incident. Garage reports are not.
     */
    public function requiresReportedTime(): bool
    {
        return $this === self::Road;
    }

    /**
     * All location values as plain strings.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $case) => $case->value,
            self::cases()
        );
    }
}
