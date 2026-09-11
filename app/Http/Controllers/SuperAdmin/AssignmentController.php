<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * Assign a user as a Company Director.
     */
    public function assignDirector(Request $request, Company $company): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Prevent super admins from being company directors
        if ($user->isSuperAdmin()) {
            return back()->with('error', __('messages.super_admin.assignments.super_admin_cannot_be_director'));
        }

        // Check if already a director
        $existing = $company->users()->whereKey($user->id)->first();

        if ($existing) {
            return back()->with('error', __('messages.super_admin.assignments.already_director', ['name' => $user->name]));
        }

        $company->users()->attach($user->id, [
            'role'      => 'director',
            'is_active' => true,
        ]);

        return back()->with('success', __('messages.super_admin.assignments.director_assigned', [
            'name'    => $user->name,
            'company' => $company->name,
        ]));
    }

    /**
     * Remove a user from Company Directors.
     */
    public function removeDirector(Company $company, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $company->users()->detach($user->id);

        return back()->with('success', __('messages.super_admin.assignments.director_removed', [
            'name'    => $user->name,
            'company' => $company->name,
        ]));
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
