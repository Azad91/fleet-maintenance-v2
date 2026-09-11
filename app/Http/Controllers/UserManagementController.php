<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Garage;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(): View|RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $garageId = $this->requireCurrentGarageId();

        if ($garageId === null) {
            return redirect()->route('garage.selection')
                ->with('error', __('messages.flash.no_garage'));
        }

        $users = User::query()
            ->whereHas('garages', fn ($query) => $query->whereKey($garageId))
            ->with(['garages' => fn ($query) => $query->whereKey($garageId)])
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'roles' => RoleEnum::garageRoleLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $garageId = $this->requireCurrentGarageId();

        if ($garageId === null) {
            return back()->with('error', __('messages.flash.no_current_garage'));
        }

        $data = $this->validateUser($request, null, true);

        $this->userService->createUserWithGarageRole($data, $garageId, true);

        return redirect()->route('users.index')
            ->with('success', __('messages.flash.user_created'));
    }

    public function edit(User $user): View|RedirectResponse
    {
        $this->authorize('update', $user);

        $garageId = $this->requireCurrentGarageId();

        if ($garageId === null) {
            return redirect()->route('garage.selection')
                ->with('error', __('messages.flash.no_garage'));
        }

        $garageRole = $this->garageRoleFor($user, $garageId);

        if ($garageRole === null && auth()->user()->isSuperAdmin()) {
            $garageRole = (object) [
                'role'      => 'admin',
                'is_active' => true,
            ];
        }

        if ($garageRole === null) {
            return redirect()->route('users.index')
                ->with('error', __('messages.flash.user_not_in_garage'));
        }

        return view('users.edit', [
            'user'       => $user,
            'roles'      => RoleEnum::garageRoleLabels(),
            'garageRole' => $garageRole,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $garageId = $this->requireCurrentGarageId();

        if ($garageId === null) {
            return back()->with('error', __('messages.flash.no_current_garage'));
        }

        $garageRole = $this->garageRoleFor($user, $garageId);

        if ($garageRole === null && $user->is(auth()->user()) && $user->isSuperAdmin()) {
            $garageRole = (object) ['role' => 'admin', 'is_active' => true];
        }

        if ($garageRole === null) {
            return back()->with('error', __('messages.flash.user_not_in_garage'));
        }

        $data = $this->validateUser($request, $user, false);

        if ($user->is(auth()->user())) {
            if ($data['role'] !== 'admin') {
                return back()
                    ->withErrors(['role' => __('messages.flash.self_role_change')])
                    ->withInput();
            }
            if (! $data['is_active']) {
                return back()
                    ->withErrors(['is_active' => __('messages.flash.self_deactivate')])
                    ->withInput();
            }
        }

        $this->userService->updateUserWithGarageRole($user, $data, $garageId);

        return redirect()->route('users.index')
            ->with('success', __('messages.flash.user_updated', ['name' => $user->name]));
    }

    private function validateUser(Request $request, ?User $user = null, bool $creating = true): array
    {
        // Password rules: use Laravel's default strength (min 8, letters + numbers).
        // On update, the password is optional — leave blank to keep the current one.
        $passwordRules = $creating
            ? ['required', 'confirmed', Password::defaults()]
            : ['nullable', 'confirmed', Password::defaults()];

        return $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role'      => ['required', Rule::in(RoleEnum::garageRoles())],
            'password'  => $passwordRules,
            'is_active' => [$creating ? 'nullable' : 'required', 'boolean'],
        ]);
    }

    private function garageRoleFor(User $user, int $garageId): ?object
    {
        $garage = $user->garages()
            ->whereKey($garageId)
            ->first();

        return $garage?->pivot;
    }

    private function requireCurrentGarageId(): ?int
    {
        $id = Garage::getCurrentId();

        return $id !== null ? (int) $id : null;
    }
}
