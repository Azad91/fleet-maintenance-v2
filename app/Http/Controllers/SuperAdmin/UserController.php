<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UserStoreRequest;
use App\Http\Requests\SuperAdmin\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * List all users with their garage/company count.
     */
    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $query = User::withCount(['garages']);

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search by name, email, or employee code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('employee_code', 'ILIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(config('settings.pagination', 25));

        return view('super-admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $this->ensureSuperAdmin();

        return view('super-admin.users.create');
    }

    /**
     * Store a newly created user.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $employeeCode = $validated['employee_code'] ?? $this->generateEmployeeCode();
        $pinWasGenerated = empty($validated['pin']);
        $pin = $validated['pin'] ?? $this->generatePin();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'employee_code' => $employeeCode,
            'pin' => Hash::make($pin),
            'pin_is_default' => $pinWasGenerated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Bütün istifadəçi mənbəli dəyərlər escape olunur — translation
        // `<code>` / `<strong>` HTML saxlayır, ona görə blade `{!! !!}`
        // istifadə edir. `:name` user tərəfindən daxil edildiyi üçün
        // escape mütləqdir.
        return redirect()
            ->route('super-admin.users.index')
            ->with('success', __('messages.super_admin.users.created', [
                'name' => e($user->name),
                'code' => e($employeeCode),
                'pin' => e($pin),
            ]));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $this->ensureSuperAdmin();

        $user->load(['garages', 'currentGarage']);

        return view('super-admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user.
     */
    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        // Domain rule that is not expressible as a validation rule:
        // a super admin may not edit another super admin.
        if ($user->isSuperAdmin() && $user->isNot(auth()->user())) {
            return back()->with('error', __('messages.super_admin.users.cannot_edit_other_super_admin'));
        }

        $validated = $request->validated();

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($validated['employee_code'])) {
            $updateData['employee_code'] = $validated['employee_code'];
        }

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if (! empty($validated['pin'])) {
            $updateData['pin'] = Hash::make($validated['pin']);
            $updateData['pin_is_default'] = false;
        }

        $user->update($updateData);

        return redirect()
            ->route('super-admin.users.index')
            ->with('success', __('messages.super_admin.users.updated', ['name' => $user->name]));
    }

    /**
     * Soft delete the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        // Prevent deleting self
        if ($user->is(auth()->user())) {
            return back()->with('error', __('messages.super_admin.users.cannot_delete_self'));
        }

        // Prevent deleting a super admin
        if ($user->isSuperAdmin()) {
            return back()->with('error', __('messages.super_admin.users.cannot_delete_super_admin'));
        }

        $user->delete();

        return redirect()
            ->route('super-admin.users.index')
            ->with('success', __('messages.super_admin.users.deleted', ['name' => $user->name]));
    }

    /**
     * Generate a unique employee code.
     */
    private function generateEmployeeCode(): string
    {
        do {
            $code = 'EMP-'.strtoupper(Str::random(6));
        } while (User::where('employee_code', $code)->exists());

        return $code;
    }

    /**
     * Generate a random 4-digit PIN.
     */
    private function generatePin(): string
    {
        return (string) random_int(1000, 9999);
    }
}
