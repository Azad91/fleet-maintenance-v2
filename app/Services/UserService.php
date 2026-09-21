<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

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
            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => now(), // ✅ Auto-verified: qaraj admini yaradıb
            ])->save();

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

            return $user->fresh(['garages']);
        });
    }
}
