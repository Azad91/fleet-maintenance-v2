<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Complaint;
use App\Models\User;
use App\Services\GarageContext;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::complaintRoles()
        ));
    }

    public function view(User $user, ?Complaint $complaint = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($complaint && $complaint->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::complaintRoles()
        ));
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::complaintRoles()
        ));
    }

    public function update(User $user, ?Complaint $complaint = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($complaint && $complaint->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::complaintRoles()
        ));
    }

    public function delete(User $user, ?Complaint $complaint = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($complaint && $complaint->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        // Yalnız admin + manager silə bilər
        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::COMPLAINT_MANAGER->value]
        ));
    }

    public function close(User $user, ?Complaint $complaint = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($complaint && $complaint->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        // Yalnız admin + manager bağlaya bilər
        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::COMPLAINT_MANAGER->value]
        ));
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Yalnız admin + manager import edə bilər
        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::COMPLAINT_MANAGER->value]
        ));
    }
}
