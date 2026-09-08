<?php

namespace App\Enums;

enum RoleEnum: string
{
    // Global user rolları
    case SUPER_ADMIN = 'super_admin';
    case USER = 'user';

    // Garage-level rollar
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
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::USER => 'İstifadəçi',
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

    public static function globalRoles(): array
    {
        return [self::SUPER_ADMIN->value, self::USER->value];
    }

    public static function garageRoles(): array
    {
        return [
            self::ADMIN->value,
            self::COMPLAINT->value,
            self::WAREHOUSE->value,
            self::DAILY_KM->value,
            self::DAILY_STATUS->value,
            self::DIRECTORATE->value,
            self::MANAGER->value,
            self::VIEWER->value,
        ];
    }

    public static function labels(): array
    {
        return array_reduce(self::cases(), function ($carry, $case) {
            $carry[$case->value] = $case->label();

            return $carry;
        }, []);
    }

    public static function garageRoleLabels(): array
    {
        $garageRoles = self::garageRoles();
        return array_filter(
            self::labels(),
            fn($key) => in_array($key, $garageRoles),
            ARRAY_FILTER_USE_KEY
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
