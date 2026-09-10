<?php

namespace App\Enums;

enum RoleEnum: string
{
    // Global user roles
    case SUPER_ADMIN = 'super_admin';
    case USER = 'user';

    // Company-level roles
    case DIRECTOR = 'director';

    // Garage-level roles
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
            self::USER => 'User',
            self::DIRECTOR => 'Company Director',
            self::ADMIN => 'Garage Admin',
            self::COMPLAINT => 'Complaint Manager',
            self::WAREHOUSE => 'Warehouse Manager',
            self::DAILY_KM => 'Daily KM Manager',
            self::DAILY_STATUS => 'Daily Status Manager',
            self::DIRECTORATE => 'Directorate (Read Only)',
            self::MANAGER => 'Manager',
            self::VIEWER => 'Viewer',
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
            fn ($key) => in_array($key, $garageRoles),
            ARRAY_FILTER_USE_KEY
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}