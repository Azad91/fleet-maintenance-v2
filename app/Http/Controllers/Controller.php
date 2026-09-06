<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests; // ✅ ƏLAVƏ EDİLDİ

    /**
     * Cari qaraj və company ID-lərini götürüb data-ya əlavə edir
     */
    protected function addGarageContext(array $data): array
    {
        $data['garage_id'] = Garage::getCurrentId();
        $data['company_id'] = Garage::getCurrentCompanyId();
        return $data;
    }

    /**
     * Rol string-lərini RoleEnum-dan al
     */
    protected function getRoleString(array|string $roles): string
    {
        if (is_string($roles)) {
            return $roles;
        }
        return implode(',', $roles);
    }
}
