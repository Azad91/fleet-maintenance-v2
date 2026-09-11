<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::warehouseRoles()
        ));
    }

    public function view(User $user, ?Warehouse $warehouse = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($warehouse && $warehouse->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::warehouseRoles()
        ));
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::warehouseRoles()
        ));
    }

    public function update(User $user, ?Warehouse $warehouse = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($warehouse && $warehouse->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::warehouseRoles()
        ));
    }

    public function delete(User $user, ?Warehouse $warehouse = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($warehouse && $warehouse->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        // Yalnız admin + manager silə bilər
        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::WAREHOUSE_MANAGER->value]
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
            [RoleEnum::WAREHOUSE_MANAGER->value]
        ));
    }
}
