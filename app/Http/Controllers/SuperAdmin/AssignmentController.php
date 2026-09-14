<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\PivotAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    public function __construct(
        protected PivotAuditService $pivotAuditor
    ) {}

    /**
     * Assign a user as a Company Director.
     *
     * Business rule: a company has AT MOST one active director. When a
     * new director is assigned, the currently active director (if any)
     * is automatically deactivated — the pivot row is preserved with
     * `is_active = false` for audit purposes, not detached.
     */
    public function assignDirector(Request $request, Company $company): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newDirector = User::findOrFail($validated['user_id']);

        if ($newDirector->isSuperAdmin()) {
            return back()->with('error', __(
                'messages.super_admin.assignments.super_admin_cannot_be_director'
            ));
        }

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
            $currentDirector = $company->directors()->first();

            if ($currentDirector) {
                $deactivatedDirector = $currentDirector;

                $company->users()->updateExistingPivot($currentDirector->id, [
                    'is_active' => false,
                ]);
            }

            $alreadyAttached = $company->users()
                ->whereKey($newDirector->id)
                ->exists();

            if ($alreadyAttached) {
                $company->users()->updateExistingPivot($newDirector->id, [
                    'role' => 'director',
                    'is_active' => true,
                ]);
            } else {
                $company->users()->attach($newDirector->id, [
                    'role' => 'director',
                    'is_active' => true,
                ]);
            }
        });

        // Audit: record the pivot change on the Company.
        $this->pivotAuditor->log(
            subject: $company,
            event: $deactivatedDirector ? 'director_changed' : 'director_assigned',
            oldValues: $deactivatedDirector
                ? ['director_id' => $deactivatedDirector->id, 'director_name' => $deactivatedDirector->name]
                : null,
            newValues: ['director_id' => $newDirector->id, 'director_name' => $newDirector->name],
            companyId: $company->id,
        );

        $message = $deactivatedDirector
            ? __('messages.super_admin.assignments.director_changed', [
                'new' => $newDirector->name,
                'old' => $deactivatedDirector->name,
                'company' => $company->name,
            ])
            : __('messages.super_admin.assignments.director_assigned', [
                'name' => $newDirector->name,
                'company' => $company->name,
            ]);

        return back()->with('success', $message);
    }

    /**
     * Remove a user from Company Directors.
     */
    public function removeDirector(Company $company, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $company->users()->detach($user->id);

        // Audit: record the pivot removal on the Company.
        $this->pivotAuditor->log(
            subject: $company,
            event: 'director_removed',
            oldValues: ['director_id' => $user->id, 'director_name' => $user->name],
            newValues: null,
            companyId: $company->id,
        );

        return back()->with('success', __(
            'messages.super_admin.assignments.director_removed',
            ['name' => $user->name, 'company' => $company->name]
        ));
    }
}
