<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MotorOilDetail;

class MotorOilPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'directorate']);
    }

    public function view(User $user, MotorOilDetail $detail): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'directorate']);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole('admin');
    }

    public function update(User $user, MotorOilDetail $detail): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole('admin');
    }

    public function delete(User $user, MotorOilDetail $detail): bool
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
