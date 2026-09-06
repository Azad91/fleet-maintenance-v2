<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DailyKmRecord;

class DailyKmRecordPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_km', 'directorate']);
    }

    public function view(User $user, DailyKmRecord $record): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_km', 'directorate']);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_km']);
    }

    public function update(User $user, DailyKmRecord $record): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_km']);
    }

    public function delete(User $user, DailyKmRecord $record): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_km']);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'daily_km']);
    }
}
