<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use App\Services\Reports\ReportScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;

    protected Company $companyB;

    protected Garage $garageA1;

    protected Garage $garageA2;

    protected Garage $garageB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create();
        $this->companyB = Company::factory()->create();

        $this->garageA1 = Garage::factory()->create(['company_id' => $this->companyA->id]);
        $this->garageA2 = Garage::factory()->create(['company_id' => $this->companyA->id]);
        $this->garageB1 = Garage::factory()->create(['company_id' => $this->companyB->id]);

        GarageContext::clear();
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================== SUPER ADMIN ====================

    public function test_super_admin_sees_all_garages_on_platform(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $scope = ReportScope::for($superAdmin, 'warehouse');

        $this->assertCount(3, $scope->garageIds);
        $this->assertContains($this->garageA1->id, $scope->garageIds);
        $this->assertContains($this->garageA2->id, $scope->garageIds);
        $this->assertContains($this->garageB1->id, $scope->garageIds);
        $this->assertNull($scope->userId);
        $this->assertTrue($scope->readOnly);
        $this->assertTrue($scope->aggregateOnly);
    }

    // ==================== DIRECTOR ====================

    public function test_director_sees_only_own_company_garages(): void
    {
        $director = User::factory()->create(['role' => 'user']);
        $this->companyA->users()->attach($director->id, [
            'role'      => 'director',
            'is_active' => true,
        ]);

        $scope = ReportScope::for($director, 'complaint');

        $this->assertCount(2, $scope->garageIds);
        $this->assertContains($this->garageA1->id, $scope->garageIds);
        $this->assertContains($this->garageA2->id, $scope->garageIds);
        $this->assertNotContains($this->garageB1->id, $scope->garageIds);
        $this->assertTrue($scope->readOnly);
    }

    public function test_director_without_company_gets_empty_scope(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Not attached to any company
        $scope = ReportScope::for($user, 'warehouse');

        $this->assertEmpty($scope->garageIds);
        $this->assertFalse($scope->hasAccess());
    }

    // ==================== GARAGE ADMIN ====================

    public function test_garage_admin_sees_only_current_garage(): void
    {
        $admin = User::factory()->create(['role' => 'user']);
        $admin->garages()->attach($this->garageA1->id, [
            'role'      => 'admin',
            'is_active' => true,
        ]);

        GarageContext::set($this->garageA1->id, $this->companyA->id);

        $scope = ReportScope::for($admin, 'warehouse');

        $this->assertSame([$this->garageA1->id], $scope->garageIds);
        $this->assertNull($scope->userId);
        $this->assertFalse($scope->readOnly);
    }

    // ==================== MANAGER vs WORKER ====================

    public function test_manager_sees_all_records_in_garage(): void
    {
        $manager = User::factory()->create(['role' => 'user']);
        $manager->garages()->attach($this->garageA1->id, [
            'role'      => 'warehouse_manager',
            'is_active' => true,
        ]);

        GarageContext::set($this->garageA1->id, $this->companyA->id);

        $scope = ReportScope::for($manager, 'warehouse');

        $this->assertSame([$this->garageA1->id], $scope->garageIds);
        $this->assertNull($scope->userId, 'Manager must see all records');
    }

    public function test_worker_sees_only_own_records(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garageA1->id, [
            'role'      => 'warehouse_worker',
            'is_active' => true,
        ]);

        GarageContext::set($this->garageA1->id, $this->companyA->id);

        $scope = ReportScope::for($worker, 'warehouse');

        $this->assertSame([$this->garageA1->id], $scope->garageIds);
        $this->assertSame($worker->id, $scope->userId, 'Worker must be filtered by own id');
    }

    public function test_worker_in_different_domain_is_not_filtered(): void
    {
        // Complaint worker checking warehouse reports — shouldn't happen
        // (route middleware prevents it), but scope logic should still be consistent.
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garageA1->id, [
            'role'      => 'complaint_worker',
            'is_active' => true,
        ]);

        GarageContext::set($this->garageA1->id, $this->companyA->id);

        $scope = ReportScope::for($worker, 'warehouse');

        // Worker of a DIFFERENT domain → no userId filter (treated as manager-ish)
        $this->assertNull($scope->userId);
    }

    // ==================== MISSING GARAGE CONTEXT ====================

    public function test_no_garage_context_returns_empty_scope(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($this->garageA1->id, [
            'role'      => 'admin',
            'is_active' => true,
        ]);

        GarageContext::clear();

        $scope = ReportScope::for($user, 'warehouse');

        $this->assertEmpty($scope->garageIds);
        $this->assertFalse($scope->hasAccess());
    }
}
