<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            'admin',
            'complaint',
            'warehouse',
            'daily_km',
            'daily_status',
            'directorate',
            'bus'
        ]);
    }
}
