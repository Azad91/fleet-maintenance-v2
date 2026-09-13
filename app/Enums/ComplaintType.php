<?php

namespace App\Enums;

enum ComplaintType: string
{
    case Accident    = 'accident';
    case Breakdown   = 'breakdown';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return __('enums.complaint_type.' . $this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Accident    => '🚗',
            self::Breakdown   => '⚠️',
            self::Maintenance => '🔧',
        };
    }

    /**
     * Bootstrap 5 color name — used for `badge bg-{color}`.
     */
    public function bootstrapColor(): string
    {
        return match ($this) {
            self::Accident    => 'danger',
            self::Breakdown   => 'warning',
            self::Maintenance => 'info',
        };
    }

    public function isAccident(): bool
    {
        return $this === self::Accident;
    }

    public function isBreakdown(): bool
    {
        return $this === self::Breakdown;
    }

    public function isMaintenance(): bool
    {
        return $this === self::Maintenance;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
