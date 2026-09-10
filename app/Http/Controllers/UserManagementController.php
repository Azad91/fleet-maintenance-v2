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
                ->with('error', 'İstifadəçi siyahısını görmək üçün əvvəlcə qaraj seçin.');
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
            return back()->with('error', 'Cari qaraj təyin olunmayıb. Səhifəni yeniləyin.');
        }

        $data = $this->validateUser($request, null, true);

        $this->userService->createUserWithGarageRole($data, $garageId, true);

        return redirect()->route('users.index')
            ->with('success', 'Yeni istifadəçi yaradıldı və cari qaraja təyin edildi.');
    }

    public function edit(User $user): View|RedirectResponse
    {
        $this->authorize('update', $user);

        $garageId = $this->requireCurrentGarageId();

        if ($garageId === null) {
            return redirect()->route('garage.selection')
                ->with('error', 'İstifadəçini redaktə etmək üçün əvvəlcə qaraj seçin.');
        }

        $garageRole = $this->garageRoleFor($user, $garageId);

        // ✅ Super admin həmişə özünü redaktə edə bilər
        // Əgər super_admin cari qaraja üzv deyilsə, sintetik pivot göstər
        if ($garageRole === null && auth()->user()->isSuperAdmin()) {
            $garageRole = (object) [
                'role'      => 'admin',
                'is_active' => true,
            ];
        }

        if ($garageRole === null) {
            return redirect()->route('users.index')
                ->with('error', 'Bu istifadəçi cari qaraja aid deyil.');
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
            return back()->with('error', 'Cari qaraj təyin olunmayıb.');
        }

        $garageRole = $this->garageRoleFor($user, $garageId);

        // Super admin özünü redaktə edirsə və pivot yoxdursa — sintetik pivot
        if ($garageRole === null && $user->is(auth()->user()) && $user->isSuperAdmin()) {
            $garageRole = (object) ['role' => 'admin', 'is_active' => true];
        }

        if ($garageRole === null) {
            return back()->with('error', 'Bu istifadəçi cari qaraja aid deyil.');
        }

        $data = $this->validateUser($request, $user, false);

        // ✅ Self-lockout qoruması (təkmilləşdirilmiş)
        if ($user->is(auth()->user())) {
            if ($data['role'] !== 'admin') {
                return back()
                    ->withErrors(['role' => 'Öz admin rolunuzu dəyişə bilməzsiniz.'])
                    ->withInput();
            }
            if (! $data['is_active']) {
                return back()
                    ->withErrors(['is_active' => 'Öz hesabınızı passiv edə bilməzsiniz.'])
                    ->withInput();
            }
        }

        $this->userService->updateUserWithGarageRole($user, $data, $garageId);

        return redirect()->route('users.index')
            ->with('success', "{$user->name} istifadəçisinin məlumatları yeniləndi.");
    }

    /**
     * Validasiya qaydalarını qurur.
     *
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null, bool $creating = true): array
    {
        $passwordRules = $creating
            ? ['required', 'string', 'min:8', 'confirmed']
            : ['nullable', 'string', 'min:8', 'confirmed'];

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

    /**
     * İstifadəçinin cari qarajdakı pivotunu qaytarır (yoxdursa null).
     */
    private function garageRoleFor(User $user, int $garageId): ?object
    {
        $garage = $user->garages()
            ->whereKey($garageId)
            ->first();

        return $garage?->pivot;
    }

    /**
     * Cari qaraj ID-sini qaytarır və ya null.
     */
    private function requireCurrentGarageId(): ?int
    {
        $id = Garage::getCurrentId();

        return $id !== null ? (int) $id : null;
    }
}
