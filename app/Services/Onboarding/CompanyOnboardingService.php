<?php

namespace App\Services\Onboarding;

use App\Models\Company;
use App\Models\User;
use App\Services\PivotAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Handles atomic creation of a Company together with its first Director.
 */
class CompanyOnboardingService
{
    public function __construct(
        protected PivotAuditService $pivotAuditor
    ) {}

    public function createWithDirector(array $companyData, array $directorData): Company
    {
        return DB::transaction(function () use ($companyData, $directorData) {
            $company = Company::create([
                'name' => $companyData['name'],
                'slug' => $companyData['slug'],
                'email' => $companyData['email'] ?? null,
                'phone' => $companyData['phone'] ?? null,
                'address' => $companyData['address'] ?? null,
                'is_active' => $companyData['is_active'] ?? true,
            ]);

            $director = User::create([
                'name' => $directorData['name'],
                'email' => $directorData['email'],
                'password' => Hash::make($directorData['password']),
                'employee_code' => $this->generateDirectorCode(),
                'pin' => Hash::make($directorData['pin']),
                'pin_is_default' => true,
                'is_active' => true,
            ]);

            $company->users()->attach($director->id, [
                'role' => 'director',
                'is_active' => true,
            ]);

            // Audit: record the pivot attachment on the Company.
            $this->pivotAuditor->log(
                subject: $company,
                event: 'director_assigned',
                oldValues: null,
                newValues: ['director_id' => $director->id, 'director_name' => $director->name],
                companyId: $company->id,
            );

            return $company->fresh(['directors']);
        });
    }

    private function generateDirectorCode(): string
    {
        do {
            $code = 'DIR-'.strtoupper(Str::random(6));
        } while (User::withTrashed()->where('employee_code', $code)->exists());

        return $code;
    }
}
