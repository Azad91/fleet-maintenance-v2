<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Garage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MakesSuperAdminWithMfa;

class DashboardStatsTest extends TestCase
{
    use MakesSuperAdminWithMfa;
    use RefreshDatabase;

    protected function asSuperAdmin()
    {
        $sa = $this->makeSuperAdminWithMfa();

        return $this->actingAs($sa);
    }

    // ==================================================================
    // 1. SOFT-DELETED BUSES ARE EXCLUDED
    // ==================================================================

    public function test_soft_deleted_buses_are_excluded_from_stats(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);

        Bus::withoutGlobalScope('garage')->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'dqn' => 'ACTIVE-001',
            'is_active' => true,
        ]);

        $deletedBus = Bus::withoutGlobalScope('garage')->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'dqn' => 'DELETED-001',
            'is_active' => true,
        ]);
        $deletedBus->delete();

        $response = $this->asSuperAdmin()
            ->get(route('super-admin.dashboard'));

        $response->assertOk();

        // The view binds `$stats['buses_total']` into the page.
        // With one active bus + one soft-deleted bus, the rendered
        // "Total Buses" value must be 1, not 2.
        $response->assertSee('Total Buses', false);
        $response->assertViewHas('stats', function ($stats) {
            return $stats['buses_total'] === 1;
        });
    }

    // ==================================================================
    // 2. SOFT-DELETED COMPLAINTS ARE EXCLUDED FROM OPEN COUNT
    // ==================================================================

    public function test_soft_deleted_complaints_are_excluded_from_open_count(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        $bus = Bus::withoutGlobalScope('garage')->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'dqn' => 'CMP-001',
            'is_active' => true,
        ]);

        // One live open complaint
        Complaint::withoutGlobalScope('garage')->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'bus_id' => $bus->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        // One soft-deleted open complaint — must be ignored
        $deletedComplaint = Complaint::withoutGlobalScope('garage')->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'bus_id' => $bus->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
        ]);
        $deletedComplaint->delete();

        $response = $this->asSuperAdmin()
            ->get(route('super-admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['complaints_open'] === 1;
        });
    }

    // ==================================================================
    // 3. COMPLETED COMPLAINTS ARE NOT "OPEN"
    // ==================================================================

    public function test_completed_complaints_are_not_counted_as_open(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        $bus = Bus::withoutGlobalScope('garage')->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'dqn' => 'CMP-002',
            'is_active' => true,
        ]);

        Complaint::withoutGlobalScope('garage')->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'bus_id' => $bus->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        Complaint::withoutGlobalScope('garage')->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'bus_id' => $bus->id,
            'yer' => 'garage',
            'status' => 'completed',
            'complaint_type' => 'breakdown',
        ]);

        $response = $this->asSuperAdmin()
            ->get(route('super-admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['complaints_open'] === 1;
        });
    }
}
