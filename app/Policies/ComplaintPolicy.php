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

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function view(User $user, ?Complaint $complaint = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($complaint && $complaint->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
        ]);
    }

        public function update(User $user, ?Complaint $complaint = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // ✅ ƏLAVƏ: Modelin qarajını yoxla
        if ($complaint && $complaint->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
        ]);
    }

    public function delete(User $user, ?Complaint $complaint = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // ✅ ƏLAVƏ
        if ($complaint && $complaint->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function close(User $user, ?Complaint $complaint = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // ✅ ƏLAVƏ
        if ($complaint && $complaint->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
        ]);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
        ]);
    }
}
