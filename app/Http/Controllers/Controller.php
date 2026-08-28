<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Session-dan qaraj və company ID-lərini götürüb data-ya əlavə edir
     */
    protected function addGarageContext(array $data): array
    {
        $data['garage_id'] = session('current_garage_id');
        $data['company_id'] = session('current_company_id');
        return $data;
    }
}
