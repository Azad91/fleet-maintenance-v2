<?php

namespace App\Services;

use Illuminate\Support\Facades\Context;

class GarageContext
{
    protected static ?int $garageId = null;
    protected static ?int $companyId = null;

    public static function set(int $garageId, ?int $companyId = null): void
    {
        static::$garageId = $garageId;
        static::$companyId = $companyId;
        Context::add('current_garage_id', $garageId);
        Context::add('current_company_id', $companyId);
    }

    public static function getGarageId(): ?int
    {
        return static::$garageId ?? Context::get('current_garage_id');
    }

    public static function getCompanyId(): ?int
    {
        return static::$companyId ?? Context::get('current_company_id');
    }

    public static function clear(): void
    {
        static::$garageId = null;
        static::$companyId = null;
        Context::forget('current_garage_id');
        Context::forget('current_company_id');
    }

    public static function has(): bool
    {
        return static::getGarageId() !== null;
    }
}


