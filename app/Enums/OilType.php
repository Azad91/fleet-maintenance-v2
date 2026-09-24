<?php

namespace App\Enums;

enum OilType: string
{
    case Motor = 'motor';
    case Gearbox = 'gearbox';
    case Axle = 'axle';

    public function label(): string
    {
        return __('enums.oil_type.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Motor => '🛢️',
            self::Gearbox => '⚙️',
            self::Axle => '🔩',
        };
    }

    public function bootstrapColor(): string
    {
        return match ($this) {
            self::Motor => 'warning',
            self::Gearbox => 'info',
            self::Axle => 'primary',
        };
    }

    public function usesBrand(): bool
    {
        return $this === self::Gearbox;
    }

    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
