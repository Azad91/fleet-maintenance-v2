<?php

use App\Enums\RoleEnum;

return [
    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    */
    'app_name' => env('APP_NAME', 'Fleet Maintenance'),
    'version' => '1.0.0',
    'pagination' => env('APP_PAGINATION', 25),
    'timezone' => env('APP_TIMEZONE', 'Asia/Baku'),
    'locale' => env('APP_LOCALE', 'az'),

    /*
    |--------------------------------------------------------------------------
    | Role Names
    |--------------------------------------------------------------------------
    */
    'roles' => RoleEnum::labels(),

    /*
    |--------------------------------------------------------------------------
    | Status Colors
    |--------------------------------------------------------------------------
    */
    'status_colors' => [
        'gözləmədə' => 'warning',
        'işdə' => 'primary',
        'həll olundu' => 'success',
        'aktiv' => 'success',
        'passiv' => 'danger',
        'temir' => 'warning',
    ],

    /*
    |--------------------------------------------------------------------------
    | Complaint Types
    |--------------------------------------------------------------------------
    */
    'complaint_types' => [
        'qezali' => '🚗 Qəzalı',
        'nasazliq' => '⚠️ Nasazlıq',
        'texniki_xidmet' => '🔧 Texniki Xidmət',
    ],

    /*
    |--------------------------------------------------------------------------
    | Employee Positions (İşçi Vəzifələri)
    |--------------------------------------------------------------------------
    */
    'employee_positions' => [
        'usta' => '🔧 Usta',
        'mexanik' => '🔩 Mexanik',
        'sürücü' => '🚌 Sürücü',
        'elektrik' => '⚡ Elektrik',
        'qaynaqci' => '🔥 Qaynaqçı',
        'boyakar' => '🎨 Boyakar',
        'digər' => '📌 Digər',
    ],
];
