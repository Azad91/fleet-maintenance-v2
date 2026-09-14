<?php

namespace App\Services\Onboarding;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Handles atomic creation of a Company together with its first Director.
 *
 * The business rule enforced here (see SuperAdmin spec):
 *   "Every Company MUST have exactly one active Director."
 *
 * The DB-level partial unique index (company_user_single_director)
 * enforces "at most one" — this service enforces "at least one" by
 * creating both rows inside a single transaction. If either the
 * Company or the Director fails to persist, both are rolled back.
 */
class CompanyOnboardingService
{
    /**
     * Create a Company and its first Director atomically.
     *
     * @param  array{name: string, slug: string, email?: ?string, phone?: ?string, address?: ?string, is_active?: bool}  $companyData
     * @param  array{name: string, email: string, password: string, pin: string}  $directorData
     */
    public function createWithDirector(array $companyData, array $directorData): Company
    {
        return DB::transaction(function () use ($companyData, $directorData) {
            $company = Company::create([
                'name'      => $companyData['name'],
                'slug'      => $companyData['slug'],
                'email'     => $companyData['email'] ?? null,
                'phone'     => $companyData['phone'] ?? null,
                'address'   => $companyData['address'] ?? null,
                'is_active' => $companyData['is_active'] ?? true,
            ]);

            $director = User::create([
                'name'            => $directorData['name'],
                'email'           => $directorData['email'],
                'password'        => Hash::make($directorData['password']),
                'employee_code'   => $this->generateDirectorCode(),
                'pin'             => Hash::make($directorData['pin']),
                'pin_is_default'  => true,  // Force PIN change on first login
                'is_active'       => true,
            ]);

            $company->users()->attach($director->id, [
                'role'      => 'director',
                'is_active' => true,
            ]);

            return $company->fresh(['directors']);
        });
    }

    /**
     * Generate a unique Director employee code (DIR-XXXXXX).
     *
     * The `DIR-` prefix mirrors the demo seeder and makes it easy to
     * distinguish Director accounts from regular users at a glance.
     */
    private function generateDirectorCode(): string
    {
        do {
            $code = 'DIR-'.strtoupper(Str::random(6));
        } while (User::withTrashed()->where('employee_code', $code)->exists());

        return $code;
    }
}
