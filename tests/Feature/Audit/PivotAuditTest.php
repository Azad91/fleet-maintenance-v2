<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\Onboarding\CompanyOnboardingService;
use App\Services\Onboarding\GarageOnboardingService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MakesSuperAdminWithMfa;

class PivotAuditTest extends TestCase
{
    use MakesSuperAdminWithMfa;
    use RefreshDatabase;

    // ==================================================================
    // 1. COMPANY ONBOARDING — director_assigned
    // ==================================================================

    public function test_company_onboarding_logs_director_assigned(): void
    {
        $service = app(CompanyOnboardingService::class);

        $company = $service->createWithDirector(
            companyData: [
                'name' => 'Pivot Co',
                'slug' => 'pivot-co',
                'is_active' => true,
            ],
            directorData: [
                'name' => 'Pivot Director',
                'email' => 'pivot@test.com',
                'password' => 'Str0ngPass!',
                'pin' => '1234',
            ],
        );

        $log = AuditLog::where('auditable_type', Company::class)
            ->where('auditable_id', $company->id)
            ->where('event', 'director_assigned')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('director_id', $log->new_values);
        $this->assertEquals('Pivot Director', $log->new_values['director_name']);
    }

    // ==================================================================
    // 2. GARAGE ONBOARDING — admin_assigned
    // ==================================================================

    public function test_garage_onboarding_logs_admin_assigned(): void
    {
        $company = Company::factory()->create();

        $service = app(GarageOnboardingService::class);

        $garage = $service->createWithAdmin(
            garageData: [
                'company_id' => $company->id,
                'name' => 'Pivot Garage',
                'code' => 'PG-001',
                'is_active' => true,
            ],
            adminData: [
                'name' => 'Pivot Admin',
                'email' => 'pivot-admin@test.com',
                'password' => 'Str0ngPass!',
                'pin' => '1234',
            ],
        );

        $log = AuditLog::where('auditable_type', Garage::class)
            ->where('auditable_id', $garage->id)
            ->where('event', 'admin_assigned')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('admin_id', $log->new_values);
        $this->assertEquals('Pivot Admin', $log->new_values['admin_name']);
        $this->assertEquals($garage->id, $log->garage_id);
    }

    // ==================================================================
    // 3. USER SERVICE — garage_role_assigned
    // ==================================================================

    public function test_user_service_logs_garage_role_assignment(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);

        $service = app(UserService::class);

        $user = $service->createUserWithGarageRole(
            data: [
                'name' => 'Assigned User',
                'email' => 'assigned@test.com',
                'password' => 'secret123',
                'role' => 'warehouse_manager',
            ],
            garageId: $garage->id,
        );

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('event', 'garage_role_assigned')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('warehouse_manager', $log->new_values['role']);
        $this->assertEquals($garage->id, $log->garage_id);
    }

    // ==================================================================
    // 4. USER SERVICE — garage_role_updated
    // ==================================================================

    public function test_user_service_logs_garage_role_update(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);

        $service = app(UserService::class);

        $user = $service->createUserWithGarageRole(
            data: [
                'name' => 'Role Changer',
                'email' => 'role-changer@test.com',
                'password' => 'secret123',
                'role' => 'warehouse_worker',
            ],
            garageId: $garage->id,
        );

        AuditLog::query()->delete();

        $service->updateUserWithGarageRole(
            user: $user,
            data: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'warehouse_manager',
                'is_active' => true,
            ],
            garageId: $garage->id,
        );

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('event', 'garage_role_updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('warehouse_worker', $log->old_values['role']);
        $this->assertEquals('warehouse_manager', $log->new_values['role']);
    }

    // ==================================================================
    // 5. ASSIGNMENT CONTROLLER — director_changed
    // ==================================================================

    public function test_assign_director_logs_change_on_pivot_swap(): void
    {
        $superAdmin = $this->makeSuperAdminWithMfa();
        $company = Company::factory()->create();

        $oldDirector = User::factory()->create(['role' => 'user', 'name' => 'Old Dir']);
        $company->users()->attach($oldDirector->id, ['role' => 'director', 'is_active' => true]);

        $newDirector = User::factory()->create(['role' => 'user', 'name' => 'New Dir']);

        AuditLog::query()->delete();

        $this->actingAs($superAdmin)
            ->withSession([
                'current_company_id' => $company->id,
            ])
            ->post(route('super-admin.companies.assign-director', $company), [
                'user_id' => $newDirector->id,
            ]);

        $log = AuditLog::where('auditable_type', Company::class)
            ->where('auditable_id', $company->id)
            ->where('event', 'director_changed')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Old Dir', $log->old_values['director_name']);
        $this->assertEquals('New Dir', $log->new_values['director_name']);
        $this->assertEquals($superAdmin->id, $log->user_id);
    }

    public function test_assign_first_director_logs_assignment(): void
    {
        $superAdmin = $this->makeSuperAdminWithMfa();

        $company = Company::factory()->create();
        $director = User::factory()->create(['role' => 'user']);

        AuditLog::query()->delete();

        $this->actingAs($superAdmin)
            ->withSession([
                'current_company_id' => $company->id,
            ])
            ->post(route('super-admin.companies.assign-director', $company), [
                'user_id' => $director->id,
            ]);

        $log = AuditLog::where('auditable_type', Company::class)
            ->where('auditable_id', $company->id)
            ->where('event', 'director_assigned')
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->old_values);
    }

    // ==================================================================
    // 6. ASSIGNMENT CONTROLLER — director_removed
    // ==================================================================

    public function test_remove_director_logs_removal(): void
    {
        $superAdmin = $this->makeSuperAdminWithMfa();

        $company = Company::factory()->create();
        $director = User::factory()->create(['role' => 'user', 'name' => 'To Remove']);
        $company->users()->attach($director->id, ['role' => 'director', 'is_active' => true]);

        AuditLog::query()->delete();

        $this->actingAs($superAdmin)
            ->withSession([
                'current_company_id' => $company->id,
            ])
            ->delete(route('super-admin.companies.remove-director', [$company, $director]));

        $log = AuditLog::where('auditable_type', Company::class)
            ->where('auditable_id', $company->id)
            ->where('event', 'director_removed')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('To Remove', $log->old_values['director_name']);
        $this->assertNull($log->new_values);
    }
}
