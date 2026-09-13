<?php

namespace App\Enums;

enum Location: string
{
    case Road = 'road';
    case Garage = 'garage';

    public function label(): string
    {
        return __('enums.location.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Road => '🛣️',
            self::Garage => '🏠',
        };
    }

    public function isRoad(): bool
    {
        return $this === self::Road;
    }

    public function isGarage(): bool
    {
        return $this === self::Garage;
    }

    public function requiresDriver(): bool
    {
        return $this === self::Road;
    }

    public function requiresReportedTime(): bool
    {
        return $this === self::Road;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
