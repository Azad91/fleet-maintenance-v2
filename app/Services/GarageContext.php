<?php

namespace App\Services;

class GarageContext
{
    protected static ?int $garageId = null;
    protected static ?int $companyId = null;

    public static function set(int $garageId, int $companyId): void
    {
        static::$garageId = $garageId;
        static::$companyId = $companyId;
    }

    public static function getGarageId(): ?int
    {
        return static::$garageId;
    }

    public static function getCompanyId(): ?int
    {
        return static::$companyId;
    }

    public static function clear(): void
    {
        static::$garageId = null;
        static::$companyId = null;
    }

    public static function has(): bool
    {
        return static::$garageId !== null;
    }
}
