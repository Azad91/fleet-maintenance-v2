<?php

namespace App\Services\Onboarding;

use App\Models\ComplaintType;
use App\Models\Garage;
use App\Models\User;
use App\Services\PivotAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Handles atomic creation of a Garage together with its first Admin
 * and the default catalog data every new garage needs.
 */
class GarageOnboardingService
{
    /**
     * Default complaint types created for every new garage.
     *
     * SuperAdmin can add or remove these through the UI later, but
     * starting with a standard list means the "new complaint" form
     * is usable the moment a garage is created.
     */
    private const DEFAULT_COMPLAINT_TYPES = [
        'Engine noise',
        'Tire puncture',
        'Brake problem',
        'Lighting failure',
        'Transmission problem',
        'Suspension problem',
        'Electrical problem',
        'Air conditioning failure',
        'Oil leak',
        'Other',
    ];

    public function __construct(
        protected PivotAuditService $pivotAuditor
    ) {}

    public function createWithAdmin(array $garageData, array $adminData): Garage
    {
        return DB::transaction(function () use ($garageData, $adminData) {
            $garage = Garage::create([
                'company_id' => $garageData['company_id'],
                'name' => $garageData['name'],
                'code' => $garageData['code'],
                'address' => $garageData['address'] ?? null,
                'phone' => $garageData['phone'] ?? null,
                'is_active' => $garageData['is_active'] ?? true,
            ]);

            $admin = User::create([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => Hash::make($adminData['password']),
                'employee_code' => $this->generateAdminCode(),
                'pin' => Hash::make($adminData['pin']),
                'pin_is_default' => true,
                'is_active' => true,
            ]);

            $garage->users()->attach($admin->id, [
                'role' => 'admin',
                'is_active' => true,
            ]);

            // ── Default complaint types ──
            // Every garage starts with the standard catalog so the
            // "new complaint" form works out of the box.
            $this->seedDefaultComplaintTypes($garage);

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

    /**
     * Insert the default complaint-type catalog for a freshly created
     * garage. Runs inside the same transaction as the garage creation,
     * so a failure rolls back the whole onboarding.
     *
     * HasGarageScope would auto-populate garage_id/company_id on
     * create, but we set them explicitly so the method works even
     * when called outside a normal request (queue, CLI, tests).
     */
    private function seedDefaultComplaintTypes(Garage $garage): void
    {
        foreach (self::DEFAULT_COMPLAINT_TYPES as $name) {
            ComplaintType::withoutGlobalScopes()->updateOrCreate(
                [
                    'name' => $name,
                    'garage_id' => $garage->id,
                ],
                [
                    'company_id' => $garage->company_id,
                ]
            );
        }
    }

    private function generateAdminCode(): string
    {
        do {
            $code = 'ADM-'.strtoupper(Str::random(6));
        } while (User::withTrashed()->where('employee_code', $code)->exists());

        return $code;
    }
}
