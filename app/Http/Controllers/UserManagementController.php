<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);  // ✅ ƏLAVƏ

        $garageId = $this->currentGarageId();
        $users = User::query()
            ->whereHas('garages', fn ($query) => $query->whereKey($garageId))
            ->with(['garages' => fn ($query) => $query->whereKey($garageId)])
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);  // ✅ ƏLAVƏ

        return view('users.create', ['roles' => RoleEnum::labels()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $data = $this->validateUser($request);
        $garageId = $this->currentGarageId();

        // ✅ DÜZƏLİŞ: users.role həmişə 'user' olur (DB constraint qorunur)
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'user',
        ]);

        // ✅ Qaraj rolu isə pivot cədvələ yazılır
        $user->garages()->attach($garageId, [
            'role' => $data['role'],
            'is_active' => true,
        ]);

        return redirect()->route('users.index')->with('success', 'Yeni istifadəçi yaradıldı və cari qaraja təyin edildi.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);  // ✅ ƏLAVƏ

        $garageRole = $this->garageRoleFor($user);

        return view('users.edit', [
            'user' => $user,
            'roles' => RoleEnum::labels(),
            'garageRole' => $garageRole,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $garageRole = $this->garageRoleFor($user);
        $data = $this->validateUser($request, $user, false);

        if ($user->is(auth()->user()) && ($data['role'] !== 'admin' || ! $data['is_active'])) {
            return back()->withErrors(['role' => 'Öz admin hesabınızı passiv edə və ya rolunu dəyişə bilməzsiniz.'])->withInput();
        }

        // ✅ DÜZƏLİŞ: users.role-a qaraj rolu yazılmır, toxunulmur
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            ...(! empty($data['password']) ? ['password' => $data['password']] : []),
        ]);

        // ✅ Qaraj rolu yalnız pivot cədvəldə yenilənir
        $user->garages()->updateExistingPivot($this->currentGarageId(), [
            'role' => $data['role'],
            'is_active' => $data['is_active'],
        ]);

        return redirect()->route('users.index')->with('success', "{$user->name} istifadəçisinin məlumatları yeniləndi.");
    }

    private function validateUser(Request $request, ?User $user = null, bool $creating = true): array
    {
        $passwordRules = $creating
            ? ['required', 'string', 'min:8', 'confirmed']
            : ['nullable', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(RoleEnum::garageRoles())],
            'password' => $passwordRules,
            'is_active' => [$creating ? 'nullable' : 'required', 'boolean'],
        ]);
    }

    private function garageRoleFor(User $user): object
    {
        $garage = $user->garages()->whereKey($this->currentGarageId())->firstOrFail();

        return $garage->pivot;
    }

    private function currentGarageId(): int
    {
        return (int) Garage::getCurrentId();
    }
}
