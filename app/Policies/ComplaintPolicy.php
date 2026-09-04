<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Complaint;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'complaint', 'directorate']);
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'complaint', 'directorate']);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'complaint']);
    }

    public function update(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'complaint']);
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin']);
    }

    public function close(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'complaint']);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(['admin', 'complaint']);
    }
}
