<?php

namespace App\Services;

use Illuminate\Support\Facades\Context;

class GarageContext
{
    /**
     * Cari qaraj və şirkət məlumatlarını kontekstə yazır
     */
    public static function set(int $garageId, ?int $companyId = null): void
    {
        Context::add('current_garage_id', $garageId);
        Context::add('current_company_id', $companyId);
    }

    /**
     * Cari qaraj ID-sini qaytarır
     */
    public static function getGarageId(): ?int
    {
        return Context::get('current_garage_id');
    }

    /**
     * Cari şirkət ID-sini qaytarır
     */
    public static function getCompanyId(): ?int
    {
        return Context::get('current_company_id');
    }

    /**
     * Konteksti təmizləyir
     */
    public static function clear(): void
    {
        Context::forget('current_garage_id');
        Context::forget('current_company_id');
    }

    /**
     * Kontekstdə qaraj məlumatı var?
     */
    public static function has(): bool
    {
        return Context::has('current_garage_id');
    }
}
