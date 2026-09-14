<?php

namespace App\Services\Onboarding;

use App\Models\Garage;
use App\Models\User;
use App\Services\PivotAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Handles atomic creation of a Garage together with its first Admin.
 */
class GarageOnboardingService
{
    public function __construct(
        protected PivotAuditService $pivotAuditor
    ) {}

    public function createWithAdmin(array $garageData, array $adminData): Garage
    {
        return DB::transaction(function () use ($garageData, $adminData) {
            $garage = Garage::create([
                'company_id' => $garageData['company_id'],
                'name'       => $garageData['name'],
                'code'       => $garageData['code'],
                'address'    => $garageData['address'] ?? null,
                'phone'      => $garageData['phone'] ?? null,
                'is_active'  => $garageData['is_active'] ?? true,
            ]);

            $admin = User::create([
                'name'           => $adminData['name'],
                'email'          => $adminData['email'],
                'password'       => Hash::make($adminData['password']),
                'employee_code'  => $this->generateAdminCode(),
                'pin'            => Hash::make($adminData['pin']),
                'pin_is_default' => true,
                'is_active'      => true,
            ]);

            $garage->users()->attach($admin->id, [
                'role'      => 'admin',
                'is_active' => true,
            ]);

            // Audit: record the pivot attachment on the Garage.
            $this->pivotAuditor->log(
                subject: $garage,
                event: 'admin_assigned',
                oldValues: null,
                newValues: ['admin_id' => $admin->id, 'admin_name' => $admin->name],
                garageId: $garage->id,
                companyId: $garage->company_id,
            );

            return $garage->fresh(['users', 'company']);
        });
    }

    private function generateAdminCode(): string
    {
        do {
            $code = 'ADM-'.strtoupper(Str::random(6));
        } while (User::withTrashed()->where('employee_code', $code)->exists());

        return $code;
    }
}
