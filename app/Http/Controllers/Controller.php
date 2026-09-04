<?php

namespace App\Http\Controllers;

use App\Models\Garage;

abstract class Controller
{
    /**
     * Cari qaraj və company ID-lərini götürüb data-ya əlavə edir
     */
    protected function addGarageContext(array $data): array
    {
        $data['garage_id'] = Garage::getCurrentId();
        $data['company_id'] = Garage::getCurrentCompanyId();
        return $data;
    }
}
