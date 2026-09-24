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
    |
    | NOTE: The `'roles' => RoleEnum::labels()` entry used to live here.
    | It was removed because RoleEnum::label() now calls __('roles.…'),
    | and the translator service is not yet available when config files
    | are evaluated at boot — that produced a "Target class [translator]
    | does not exist" error. Anywhere role labels are needed, call
    | RoleEnum::labels() directly instead.
    |
    */

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
