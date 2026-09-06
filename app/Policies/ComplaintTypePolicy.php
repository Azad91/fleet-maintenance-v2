<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ComplaintType;

class ComplaintTypePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'complaint', 'directorate']);
    }

    public function view(User $user, ComplaintType $type): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'complaint', 'directorate']);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole('admin');
    }

    public function update(User $user, ComplaintType $type): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole('admin');
    }

    public function delete(User $user, ComplaintType $type): bool
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
