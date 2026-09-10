<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Yeni istifadəçi yaradır və cari qaraja təyin edir.
     *
     * Bütün əməliyyatlar bir transaction içindədir — ya hamısı,
     * ya heç biri. Yarı-yaradılmış istifadəçi problemi olmaz.
     *
     * @param  array{name: string, email: string, password: string, role: string}  $data
     */
    public function createUserWithGarageRole(
        array $data,
        int $garageId,
        bool $isActive = true
    ): User {
        return DB::transaction(function () use ($data, $garageId, $isActive) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'role'     => 'user', // users.role həmişə 'user' — qaraj rolu pivotdadır
            ]);

            $user->garages()->attach($garageId, [
                'role'      => $data['role'],
                'is_active' => $isActive,
            ]);

            return $user;
        });
    }

    /**
     * İstifadəçinin profilini və qaraj rolunu yeniləyir.
     *
     * @param  array{name: string, email: string, password?: ?string, role: string, is_active: bool}  $data
     */
    public function updateUserWithGarageRole(
        User $user,
        array $data,
        int $garageId
    ): User {
        return DB::transaction(function () use ($user, $data, $garageId) {
            // users.role toxunulmur — yalnız şəxsi məlumatlar
            $userUpdate = [
                'name'  => $data['name'],
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $userUpdate['password'] = $data['password'];
            }

            $user->update($userUpdate);

            // Qaraj rolu yalnız pivotda yenilənir
            $user->garages()->updateExistingPivot($garageId, [
                'role'      => $data['role'],
                'is_active' => $data['is_active'],
            ]);

            return $user->fresh(['garages']);
        });
    }
}
