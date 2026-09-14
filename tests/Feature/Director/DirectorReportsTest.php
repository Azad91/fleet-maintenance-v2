<?php

namespace Tests\Feature\Director;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectorReportsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;

    protected Company $companyB;

    protected Garage $garageA1;

    protected Garage $garageA2;

    protected Garage $garageB;

    protected User $director;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        $this->garageA1 = Garage::factory()->create(['company_id' => $this->companyA->id]);
        $this->garageA2 = Garage::factory()->create(['company_id' => $this->companyA->id]);
        $this->garageB  = Garage::factory()->create(['company_id' => $this->companyB->id]);

        $this->director = User::factory()->create(['role' => 'user']);
        $this->companyA->users()->attach($this->director->id, [
            'role' => 'director',
            'is_active' => true,
        ]);
    }

    // ==================================================================
    // 1. DIRECTOR CAN ACCESS ALL REPORT DOMAINS
    // ==================================================================

    public function test_director_can_access_warehouse_reports(): void
    {
        $this->actingAs($this->director)
            ->get(route('director.reports.warehouse.receipt'))
            ->assertOk();

        $this->actingAs($this->director)
            ->get(route('director.reports.warehouse.low-stock'))
            ->assertOk();
    }

    public function test_director_can_access_complaint_reports(): void
    {
        $this->actingAs($this->director)
            ->get(route('director.reports.complaint.summary'))
            ->assertOk();

        $this->actingAs($this->director)
            ->get(route('director.reports.complaint.top-types'))
            ->assertOk();
    }

    public function test_director_can_access_daily_km_reports(): void
    {
        $this->actingAs($this->director)
            ->get(route('director.reports.daily-km.missing'))
            ->assertOk();
    }

    public function test_director_can_access_daily_status_reports(): void
    {
        $this->actingAs($this->director)
            ->get(route('director.reports.daily-status.distribution'))
            ->assertOk();
    }

    // ==================================================================
    // 2. DIRECTOR SEES DATA FROM ALL OWN GARAGES
    // ==================================================================

    public function test_director_sees_warehouse_items_from_all_own_garages(): void
    {
        Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA1->id,
            'company_id' => $this->companyA->id,
            'code' => 'A1-001',
            'name' => 'Garage A1 Item',
            'quantity' => 10,
        ]);

        Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA2->id,
            'company_id' => $this->companyA->id,
            'code' => 'A2-001',
            'name' => 'Garage A2 Item',
            'quantity' => 20,
        ]);

        $response = $this->actingAs($this->director)
            ->get(route('director.reports.warehouse.receipt'));

        $response->assertOk();
        $response->assertSee('Garage A1 Item');
        $response->assertSee('Garage A2 Item');
    }

    // ==================================================================
    // 3. DIRECTOR DOES NOT SEE OTHER COMPANY DATA
    // ==================================================================

    public function test_director_does_not_see_warehouse_items_from_other_companies(): void
    {
        Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA1->id,
            'company_id' => $this->companyA->id,
            'code' => 'A1-001',
            'name' => 'Garage A1 Item',
            'quantity' => 10,
        ]);

        Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $this->garageB->id,
            'company_id' => $this->companyB->id,
            'code' => 'B1-001',
            'name' => 'Garage B1 Item',
            'quantity' => 30,
        ]);

        $response = $this->actingAs($this->director)
            ->get(route('director.reports.warehouse.receipt'));

        $response->assertOk();
        $response->assertSee('Garage A1 Item');
        $response->assertDontSee('Garage B1 Item');
    }

    // ==================================================================
    // 4. NON-DIRECTOR CANNOT ACCESS DIRECTOR REPORTS
    // ==================================================================

    public function test_regular_user_cannot_access_director_reports(): void
    {
        $regular = User::factory()->create(['role' => 'user']);

        $this->actingAs($regular)
            ->get(route('director.reports.warehouse.receipt'))
            ->assertForbidden();
    }

    public function test_garage_admin_cannot_access_director_reports(): void
    {
        $admin = User::factory()->create(['role' => 'user']);
        $admin->garages()->attach($this->garageA1->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('director.reports.warehouse.receipt'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('director.reports.warehouse.receipt'))
            ->assertRedirect(route('login'));
    }

    // ==================================================================
    // 5. SHELL DETECTS DIRECTOR CONTEXT
    // ==================================================================

    public function test_director_sees_company_scope_label(): void
    {
        $response = $this->actingAs($this->director)
            ->get(route('director.reports.warehouse.receipt'));

        $response->assertOk();
        $response->assertSee(__('messages.director.reports.company_scope'), false);
    }

    public function test_director_report_tabs_link_to_director_routes(): void
    {
        $response = $this->actingAs($this->director)
            ->get(route('director.reports.warehouse.receipt'));

        $response->assertOk();
        // The tab links must point to director.reports.* routes, not reports.*
        $response->assertSee(
            route('director.reports.warehouse.usage'),
            false
        );
        $response->assertSee(
            route('director.reports.warehouse.low-stock'),
            false
        );
    }

    // ==================================================================
    // 6. GARAGE USER STILL WORKS (regression check)
    // ==================================================================

    public function test_garage_admin_still_accesses_garage_reports(): void
    {
        $admin = User::factory()->create(['role' => 'user']);
        $admin->garages()->attach($this->garageA1->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->withSession([
                'current_garage_id' => $this->garageA1->id,
                'current_company_id' => $this->companyA->id,
            ])
            ->get(route('reports.warehouse.receipt'))
            ->assertOk();
    }

    public function test_garage_admin_does_not_see_company_scope_label(): void
    {
        $admin = User::factory()->create(['role' => 'user']);
        $admin->garages()->attach($this->garageA1->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession([
                'current_garage_id' => $this->garageA1->id,
                'current_company_id' => $this->companyA->id,
            ])
            ->get(route('reports.warehouse.receipt'));

        $response->assertOk();
        $response->assertDontSee(__('messages.director.reports.company_scope'), false);
    }
}
