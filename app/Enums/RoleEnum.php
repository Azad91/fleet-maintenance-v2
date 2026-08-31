<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case COMPLAINT = 'complaint';
    case WAREHOUSE = 'warehouse';
    case DAILY_KM = 'daily_km';
    case DAILY_STATUS = 'daily_status';
    case DIRECTORATE = 'directorate';
    case MANAGER = 'manager';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Admin',
            self::COMPLAINT => 'Kartlar / Şikayətlər',
            self::WAREHOUSE => 'Anbar',
            self::DAILY_KM => 'Günlük KM',
            self::DAILY_STATUS => 'Günlük statuslar',
            self::DIRECTORATE => 'Müdiriyyət (yalnız baxış)',
            self::MANAGER => 'Menecer',
            self::VIEWER => 'Baxış',
        };
    }

    public static function labels(): array
    {
        return array_reduce(self::cases(), function ($carry, $case) {
            $carry[$case->value] = $case->label();
            return $carry;
        }, []);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
