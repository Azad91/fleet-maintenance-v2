<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        protected PivotAuditService $pivotAuditor
    ) {}

    /**
     * Create a new user and attach them to the given garage.
     *
     * Every step runs inside a single transaction — all or nothing,
     * so a half-created user can never end up in the database.
     */
    public function createUserWithGarageRole(
        array $data,
        int $garageId,
        bool $isActive = true
    ): User {
        return DB::transaction(function () use ($data, $garageId, $isActive) {
            $employeeCode = ! empty($data['employee_code'])
                ? (string) $data['employee_code']
                : $this->generateEmployeeCode();

            $pinWasProvided = ! empty($data['pin']);
            $pin = $pinWasProvided ? (string) $data['pin'] : $this->generatePin();

            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'employee_code' => $employeeCode,
                'pin' => Hash::make($pin),
                'pin_is_default' => ! $pinWasProvided,
                'email_verified_at' => now(), // ✅ Auto-verified: qaraj admini yaradıb
            ])->save();

            if ($data['role'] === 'admin' && $isActive) {
                $hasActiveAdmin = DB::table('garage_user')
                    ->where('garage_id', $garageId)
                    ->where('role', 'admin')
                    ->where('is_active', true)
                    ->exists();

                if ($hasActiveAdmin) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'role' => __('messages.flash.garage_already_has_admin'),
                    ]);
                }
            }

            $user->garages()->attach($garageId, [
                'role' => $data['role'],
                'is_active' => $isActive,
            ]);

            // Audit: record the garage pivot assignment.
            $this->pivotAuditor->log(
                subject: $user,
                event: 'garage_role_assigned',
                oldValues: null,
                newValues: ['garage_id' => $garageId, 'role' => $data['role'], 'is_active' => $isActive],
                garageId: $garageId,
            );

            $user->forgetRoleCache();

            return $user;
        });
    }

    /**
     * Update the user's profile and their garage role.
     */
    public function updateUserWithGarageRole(
        User $user,
        array $data,
        int $garageId
    ): User {
        return DB::transaction(function () use ($user, $data, $garageId) {
            $userUpdate = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $userUpdate['password'] = $data['password'];
            }

            // Capture the previous pivot state for audit purposes.
            $previousPivot = $user->garages()
                ->whereKey($garageId)
                ->first()?->pivot;

            $oldPivotValues = $previousPivot ? [
                'role' => $previousPivot->role,
                'is_active' => (bool) $previousPivot->is_active,
            ] : null;

            $user->update($userUpdate);

            if ($data['role'] === 'admin' && ! empty($data['is_active'])) {
                $hasOtherActiveAdmin = DB::table('garage_user')
                    ->where('garage_id', $garageId)
                    ->where('role', 'admin')
                    ->where('is_active', true)
                    ->where('user_id', '!=', $user->id)
                    ->exists();

                if ($hasOtherActiveAdmin) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'role' => __('messages.flash.garage_already_has_admin'),
                    ]);
                }
            }

            $user->garages()->updateExistingPivot($garageId, [
                'role' => $data['role'],
                'is_active' => $data['is_active'],
            ]);

            // Audit: record the pivot update only when something changed.
            $newPivotValues = [
                'role' => $data['role'],
                'is_active' => (bool) $data['is_active'],
            ];

            $pivotChanged = $oldPivotValues === null
                || $oldPivotValues['role'] !== $newPivotValues['role']
                || $oldPivotValues['is_active'] !== $newPivotValues['is_active'];

            if ($pivotChanged) {
                $this->pivotAuditor->log(
                    subject: $user,
                    event: 'garage_role_updated',
                    oldValues: $oldPivotValues,
                    newValues: $newPivotValues,
                    garageId: $garageId,
                );
            }

            $user->forgetRoleCache();

            return $user->fresh(['garages']);
        });
    }

    /**
     * Generate a unique employee code.
     */
    public function generateEmployeeCode(): string
    {
        do {
            $code = 'EMP-'.strtoupper(Str::random(6));
        } while (User::withTrashed()->where('employee_code', $code)->exists());

        return $code;
    }

    /**
     * Generate a random 6-digit PIN.
     */
    public function generatePin(): string
    {
        do {
            $pin = (string) random_int(100000, 999999);
        } while (in_array($pin, \App\Rules\StrongPin::WEAK_PINS, true) || count(array_unique(str_split($pin))) === 1);

        return $pin;
    }
}
