<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    /**
     * Assign a user as a Company Director.
     *
     * Business rule (see SuperAdmin spec): a company has AT MOST one
     * active director. When a new director is assigned, the currently
     * active director (if any) is automatically deactivated — the
     * pivot row is preserved with `is_active = false` for audit
     * purposes, not detached.
     *
     * The DB-level partial unique index `company_user_single_director`
     * enforces this at the storage layer; the transaction below is
     * what keeps the app from tripping that index when swapping.
     */
    public function assignDirector(Request $request, Company $company): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newDirector = User::findOrFail($validated['user_id']);

        // Prevent super admins from being company directors.
        if ($newDirector->isSuperAdmin()) {
            return back()->with('error', __(
                'messages.super_admin.assignments.super_admin_cannot_be_director'
            ));
        }

        // If the user is ALREADY the active director of this company,
        // there is nothing to do — surface a friendly error.
        $isAlreadyActive = $company->directors()
            ->whereKey($newDirector->id)
            ->exists();

        if ($isAlreadyActive) {
            return back()->with('error', __(
                'messages.super_admin.assignments.already_director',
                ['name' => $newDirector->name]
            ));
        }

        $deactivatedDirector = null;

        DB::transaction(function () use ($company, $newDirector, &$deactivatedDirector) {
            // Step 1: deactivate the current active director, if any.
            // This MUST run before Step 2, otherwise the partial unique
            // index on (company_id) WHERE role='director' AND is_active=true
            // would reject the second active row.
            $currentDirector = $company->directors()->first();

            if ($currentDirector) {
                $deactivatedDirector = $currentDirector;

                $company->users()->updateExistingPivot($currentDirector->id, [
                    'is_active' => false,
                ]);
            }

            // Step 2: attach or reactivate the new director.
            $alreadyAttached = $company->users()
                ->whereKey($newDirector->id)
                ->exists();

            if ($alreadyAttached) {
                // Reactivate an existing (previously deactivated) pivot row.
                $company->users()->updateExistingPivot($newDirector->id, [
                    'role'      => 'director',
                    'is_active' => true,
                ]);
            } else {
                $company->users()->attach($newDirector->id, [
                    'role'      => 'director',
                    'is_active' => true,
                ]);
            }
        });

        $message = $deactivatedDirector
            ? __('messages.super_admin.assignments.director_changed', [
                'new'     => $newDirector->name,
                'old'     => $deactivatedDirector->name,
                'company' => $company->name,
            ])
            : __('messages.super_admin.assignments.director_assigned', [
                'name'    => $newDirector->name,
                'company' => $company->name,
            ]);

        return back()->with('success', $message);
    }

    /**
     * Remove a user from Company Directors.
     *
     * This is the "hard" escape hatch — the pivot row is detached
     * entirely. Used when the assignment was made by mistake.
     * Prefer `assignDirector` (which deactivates the previous director)
     * for normal role rotations, so the audit trail is preserved.
     */
    public function removeDirector(Company $company, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $company->users()->detach($user->id);

        return back()->with('success', __(
            'messages.super_admin.assignments.director_removed',
            ['name' => $user->name, 'company' => $company->name]
        ));
    }
}
