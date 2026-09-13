<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function garageSession(): array
    {
        return [
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_garage_context_is_redirected_to_selection(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('garage.selection'));
    }

    public function test_garage_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'user']);
        $admin->garages()->attach($this->garage->id, [
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->withSession($this->garageSession())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_domain_worker_can_access_dashboard(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garage->id, [
            'role'      => 'complaint_worker',
            'is_active' => true,
        ]);

        $this->actingAs($worker)
            ->withSession($this->garageSession())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->withSession($this->garageSession())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_policy_file_does_not_exist(): void
    {
        // The DashboardController has no model and no gate — it is
        // protected exclusively by route middleware. This guard fails
        // if anyone reintroduces a policy binding for a controller
        // class, which is a common anti-pattern.
        //
        // Note: we check file existence rather than class_exists() so
        // the test does not trigger Composer's autoloader for a class
        // that is intentionally absent.
        $path = app_path('Policies/DashboardPolicy.php');

        $this->assertFileDoesNotExist(
            $path,
            'DashboardPolicy must not be reintroduced. Access to the '
            . 'dashboard is enforced by the auth + garage.selected '
            . 'route middleware, not by a (no-op) policy.'
        );
    }

    public function test_no_policy_is_bound_to_a_controller_class(): void
    {
        // Walk the registered policies and assert that none of them
        // map from an App\Http\Controllers\* class. Policies should
        // only be bound to Eloquent models.
        $policies = app(\Illuminate\Contracts\Auth\Access\Gate::class)
            ->getPolicyFor(\App\Http\Controllers\DashboardController::class);

        $this->assertNull(
            $policies,
            'A policy was bound to a controller class. '
            . 'Policies must be bound to models, not controllers. '
            . 'Use route middleware for controller-level authorization.'
        );
    }
}
