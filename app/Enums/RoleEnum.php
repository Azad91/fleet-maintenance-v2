<?php

namespace App\Enums;

enum RoleEnum: string
{
    // ============================================================
    // GLOBAL ROLES (users.role cədvəli)
    // ============================================================
    case SUPER_ADMIN = 'super_admin';
    case USER = 'user';

    // ============================================================
    // COMPANY-LEVEL ROLES (company_user pivot cədvəli)
    // ============================================================
    case DIRECTOR = 'director';

    // ============================================================
    // GARAGE-LEVEL ROLES (garage_user pivot cədvəli)
    // ============================================================
    case ADMIN = 'admin';

    // 📋 Şikayətlər sahəsi
    case COMPLAINT_MANAGER = 'complaint_manager';
    case COMPLAINT_WORKER = 'complaint_worker';

    // 📦 Anbar sahəsi
    case WAREHOUSE_MANAGER = 'warehouse_manager';
    case WAREHOUSE_WORKER = 'warehouse_worker';

    // 📊 Günlük KM sahəsi
    case DAILY_KM_MANAGER = 'daily_km_manager';
    case DAILY_KM_WORKER = 'daily_km_worker';

    // 📌 Günlük Status sahəsi
    case DAILY_STATUS_MANAGER = 'daily_status_manager';
    case DAILY_STATUS_WORKER = 'daily_status_worker';

    // ============================================================
    // LABELS
    // ============================================================
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::USER => 'User',
            self::DIRECTOR => 'Company Director',
            self::ADMIN => 'Garage Admin',
            self::COMPLAINT_MANAGER => 'Complaint Manager',
            self::COMPLAINT_WORKER => 'Complaint Worker',
            self::WAREHOUSE_MANAGER => 'Warehouse Manager',
            self::WAREHOUSE_WORKER => 'Warehouse Worker',
            self::DAILY_KM_MANAGER => 'Daily KM Manager',
            self::DAILY_KM_WORKER => 'Daily KM Worker',
            self::DAILY_STATUS_MANAGER => 'Daily Status Manager',
            self::DAILY_STATUS_WORKER => 'Daily Status Worker',
        };
    }

    // ============================================================
    // DOMAIN HELPERS (hansı sahəyə aiddir)
    // ============================================================
    public function domain(): ?string
    {
        return match ($this) {
            self::COMPLAINT_MANAGER, self::COMPLAINT_WORKER => 'complaint',
            self::WAREHOUSE_MANAGER, self::WAREHOUSE_WORKER => 'warehouse',
            self::DAILY_KM_MANAGER, self::DAILY_KM_WORKER => 'daily_km',
            self::DAILY_STATUS_MANAGER, self::DAILY_STATUS_WORKER => 'daily_status',
            default => null,
        };
    }

    // ============================================================
    // TIER HELPERS (manager / worker)
    // ============================================================
    public function isManager(): bool
    {
        return in_array($this, [
            self::COMPLAINT_MANAGER,
            self::WAREHOUSE_MANAGER,
            self::DAILY_KM_MANAGER,
            self::DAILY_STATUS_MANAGER,
        ], true);
    }

    public function isWorker(): bool
    {
        return in_array($this, [
            self::COMPLAINT_WORKER,
            self::WAREHOUSE_WORKER,
            self::DAILY_KM_WORKER,
            self::DAILY_STATUS_WORKER,
        ], true);
    }

    // ============================================================
    // STATIC GROUP HELPERS
    // ============================================================
    public static function globalRoles(): array
    {
        return [self::SUPER_ADMIN->value, self::USER->value];
    }

    public static function companyRoles(): array
    {
        return [self::DIRECTOR->value];
    }

    public static function garageRoles(): array
    {
        return [
            self::ADMIN->value,
            self::COMPLAINT_MANAGER->value,
            self::COMPLAINT_WORKER->value,
            self::WAREHOUSE_MANAGER->value,
            self::WAREHOUSE_WORKER->value,
            self::DAILY_KM_MANAGER->value,
            self::DAILY_KM_WORKER->value,
            self::DAILY_STATUS_MANAGER->value,
            self::DAILY_STATUS_WORKER->value,
        ];
    }

    // ============================================================
    // DOMAIN-SPECIFIC GROUPS (policy-lərdə istifadə olunacaq)
    // ============================================================
    public static function complaintRoles(): array
    {
        return [self::COMPLAINT_MANAGER->value, self::COMPLAINT_WORKER->value];
    }

    public static function warehouseRoles(): array
    {
        return [self::WAREHOUSE_MANAGER->value, self::WAREHOUSE_WORKER->value];
    }

    public static function dailyKmRoles(): array
    {
        return [self::DAILY_KM_MANAGER->value, self::DAILY_KM_WORKER->value];
    }

    public static function dailyStatusRoles(): array
    {
        return [self::DAILY_STATUS_MANAGER->value, self::DAILY_STATUS_WORKER->value];
    }

    public static function managerRoles(): array
    {
        return [
            self::COMPLAINT_MANAGER->value,
            self::WAREHOUSE_MANAGER->value,
            self::DAILY_KM_MANAGER->value,
            self::DAILY_STATUS_MANAGER->value,
        ];
    }

    public static function workerRoles(): array
    {
        return [
            self::COMPLAINT_WORKER->value,
            self::WAREHOUSE_WORKER->value,
            self::DAILY_KM_WORKER->value,
            self::DAILY_STATUS_WORKER->value,
        ];
    }

    // ============================================================
    // LABEL MAPS (UI dropdowns, config)
    // ============================================================
    public static function labels(): array
    {
        return array_reduce(self::cases(), function ($carry, $case) {
            $carry[$case->value] = $case->label();
            return $carry;
        }, []);
    }

    public static function garageRoleLabels(): array
    {
        return array_filter(
            self::labels(),
            fn ($key) => in_array($key, self::garageRoles(), true),
            ARRAY_FILTER_USE_KEY
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
