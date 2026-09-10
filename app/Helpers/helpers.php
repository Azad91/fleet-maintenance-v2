<?php

use App\Enums\RoleEnum;
use Carbon\Carbon;
use Illuminate\Support\Str;

if (! function_exists('format_km')) {
    function format_km($km)
    {
        return $km ? number_format($km, 0, ',', '.') . ' km' : '-';
    }
}

if (! function_exists('format_price')) {
    function format_price($price)
    {
        return $price ? number_format($price, 2) . ' ₼' : '-';
    }
}

if (! function_exists('format_date')) {
    function format_date($date, $format = 'd.m.Y')
    {
        return $date ? Carbon::parse($date)->format($format) : '-';
    }
}

if (! function_exists('format_datetime')) {
    function format_datetime($date, $format = 'd.m.Y H:i')
    {
        return $date ? Carbon::parse($date)->format($format) : '-';
    }
}

if (! function_exists('status_badge_class')) {
    function status_badge_class($status)
    {
        return match ($status) {
            'pending'     => 'pending',
            'in_progress' => 'in-progress',
            'completed'   => 'completed',
            'cancelled'   => 'cancelled',
            'active'      => 'active',
            'inactive'    => 'inactive',
            'repair'      => 'repair',
            default       => '',
        };
    }
}

if (! function_exists('role_label')) {
    function role_label($role)
    {
        return RoleEnum::labels()[$role] ?? $role;
    }
}

if (! function_exists('status_color')) {
    function status_color($status)
    {
        $colors = config('settings.status_colors', []);

        return $colors[$status] ?? 'secondary';
    }
}

if (! function_exists('complaint_type_label')) {
    function complaint_type_label($type)
    {
        $types = config('settings.complaint_types', []);

        return $types[$type] ?? $type;
    }
}

if (! function_exists('truncate_text')) {
    function truncate_text($text, $length = 30)
    {
        return $text ? Str::limit($text, $length) : '-';
    }
}