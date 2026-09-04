<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
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
        return view('users.create', ['roles' => RoleEnum::labels()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $garageId = $this->currentGarageId();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        $user->garages()->attach($garageId, [
            'role' => $data['role'],
            'is_active' => true,
        ]);

        return redirect()->route('users.index')->with('success', 'Yeni istifadəçi yaradıldı və cari qaraja təyin edildi.');
    }

    public function edit(User $user)
    {
        $garageRole = $this->garageRoleFor($user);

        return view('users.edit', [
            'user' => $user,
            'roles' => RoleEnum::labels(),
            'garageRole' => $garageRole,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $garageRole = $this->garageRoleFor($user);
        $data = $this->validateUser($request, $user, false);

        if ($user->is(auth()->user()) && ($data['role'] !== 'admin' || ! $data['is_active'])) {
            return back()->withErrors(['role' => 'Öz admin hesabınızı passiv edə və ya rolunu dəyişə bilməzsiniz.'])->withInput();
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            ...(! empty($data['password']) ? ['password' => $data['password']] : []),
        ]);

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
            'role' => ['required', Rule::in(RoleEnum::values())],
            'password' => $passwordRules,
            'is_active' => [$creating ? 'nullable' : 'required', 'boolean'],
        ], [
            'password.min' => 'Şifrə ən azı 8 simvol olmalıdır.',
            'password.confirmed' => 'Şifrə təkrarı uyğun deyil.',
        ]);
    }

    private function garageRoleFor(User $user): object
    {
        $garage = $user->garages()->whereKey($this->currentGarageId())->firstOrFail();
        return $garage->pivot;
    }

    private function currentGarageId(): int
    {
        return (int) \App\Models\Garage::getCurrentId();
    }
}
