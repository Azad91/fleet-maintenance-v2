<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Bus;

class BusPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'bus', 'directorate']);
    }

    public function view(User $user, Bus $bus): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'bus', 'directorate']);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole('admin');
    }

    public function update(User $user, Bus $bus): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole('admin');
    }

    public function delete(User $user, Bus $bus): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole('admin');
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole('admin');
    }
}
