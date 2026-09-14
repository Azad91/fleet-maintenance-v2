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
    'locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Role Names
    |--------------------------------------------------------------------------
    */
    // ⚠️ SİLİNDİ: `'roles' => RoleEnum::labels()` burada çağırılırdı, amma
    // `RoleEnum::label()` indi `__('roles.…')` işlədir → config yüklənərkən
    // translator hələ hazır deyil → "Target class [translator] does not exist".
    // Rol etiketləri lazım olan yerlərdə birbaşa `RoleEnum::labels()` çağırın.

    /*
    |--------------------------------------------------------------------------
    | Status Colors
    |--------------------------------------------------------------------------
    */
    'status_colors' => [
        'pending' => 'warning',
        'in_progress' => 'primary',
        'completed' => 'success',
        'cancelled' => 'secondary',
        'active' => 'success',
        'inactive' => 'danger',
        'repair' => 'warning',
    ],

    /*
    |--------------------------------------------------------------------------
    | Complaint Types
    |--------------------------------------------------------------------------
    */
    'complaint_types' => [
        'accident' => '🚗 Accident',
        'breakdown' => '⚠️ Breakdown',
        'maintenance' => '🔧 Maintenance',
    ],

    /*
    |--------------------------------------------------------------------------
    | Employee Positions
    |--------------------------------------------------------------------------
    */
    'employee_positions' => [
        'master' => '🔧 Master',
        'mechanic' => '🔩 Mechanic',
        'driver' => '🚌 Driver',
        'electrician' => '⚡ Electrician',
        'welder' => '🔥 Welder',
        'painter' => '🎨 Painter',
        'other' => '📌 Other',
    ],
];
