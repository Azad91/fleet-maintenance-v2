<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BusDailyStatus;

class BusDailyStatusPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_status', 'directorate']);
    }

    public function view(User $user, BusDailyStatus $status): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_status', 'directorate']);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_status']);
    }

    public function update(User $user, BusDailyStatus $status): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_status']);
    }

    public function delete(User $user, BusDailyStatus $status): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_status']);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_status']);
    }
}
