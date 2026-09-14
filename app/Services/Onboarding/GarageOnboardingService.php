<?php

namespace App\Services\Onboarding;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Handles atomic creation of a Garage together with its first Admin.
 *
 * The business rule enforced here (see SuperAdmin spec):
 *   "Every Garage MUST have exactly one active Admin."
 *
 * The DB-level partial unique index (garage_user_single_admin)
 * enforces "at most one" — this service enforces "at least one" by
 * creating both rows inside a single transaction. If either the
 * Garage or the Admin fails to persist, both are rolled back.
 *
 * Unlike Company/Director (company-level), Garage Admins belong to a
 * specific garage via the garage_user pivot, so the same person could
 * theoretically be admin of two different garages — the unique index
 * only forbids two ADMINS in the SAME garage.
 */
class GarageOnboardingService
{
    /**
     * Create a Garage and its first Admin atomically.
     *
     * @param  array{company_id: int, name: string, code: string, address?: ?string, phone?: ?string, is_active?: bool}  $garageData
     * @param  array{name: string, email: string, password: string, pin: string}  $adminData
     */
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
                'pin_is_default' => true,  // Force PIN change on first login
                'is_active'      => true,
            ]);

            $garage->users()->attach($admin->id, [
                'role'      => 'admin',
                'is_active' => true,
            ]);

            return $garage->fresh(['users', 'company']);
        });
    }

    /**
     * Generate a unique Garage Admin employee code (ADM-XXXXXX).
     *
     * The `ADM-` prefix mirrors the `DIR-` prefix used for Directors,
     * making it easy to distinguish account types at a glance.
     * `withTrashed()` is used so that soft-deleted accounts do not
     * block code reuse — this matches the partial unique index on
     * `users.employee_code` (WHERE deleted_at IS NULL).
     */
    private function generateAdminCode(): string
    {
        do {
            $code = 'ADM-'.strtoupper(Str::random(6));
        } while (User::withTrashed()->where('employee_code', $code)->exists());

        return $code;
    }
}
