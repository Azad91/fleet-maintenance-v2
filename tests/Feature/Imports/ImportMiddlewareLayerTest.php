<?php

namespace Tests\Feature\Imports;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Verifies the FIRST line of defense: middleware redirects the user
 * to /select-garage with a clear error message BEFORE the controller
 * is ever reached. This is the behavior users actually see.
 *
 * The companion test GarageIdGuardTest verifies the SECOND line of
 * defense (controller-level guard) with the middleware disabled.
 */
class ImportMiddlewareLayerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage  = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garage->id, [
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_complaints_import_redirects_with_error_before_controller(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('complaints.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error', __('messages.flash.no_garage'));
    }

    public function test_warehouse_import_redirects_with_error_before_controller(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('warehouses.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error', __('messages.flash.no_garage'));
    }

    public function test_buses_import_redirects_with_error_before_controller(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('buses.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error', __('messages.flash.no_garage'));
    }

    public function test_daily_km_import_redirects_with_error_before_controller(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('daily-km-records.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error', __('messages.flash.no_garage'));
    }

    public function test_bus_daily_statuses_import_redirects_with_error_before_controller(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('bus-daily-statuses.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error', __('messages.flash.no_garage'));
    }

    public function test_employees_import_redirects_with_error_before_controller(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('employees.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error', __('messages.flash.no_garage'));
    }

    public function test_drivers_import_redirects_with_error_before_controller(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('drivers.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error', __('messages.flash.no_garage'));
    }

    public function test_invalid_garage_session_redirects_with_access_denied(): void
    {
        // Session has a garage_id that the user does NOT belong to.
        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_garage_id'  => $otherGarage->id,
                'current_company_id' => $this->company->id,
            ])
            ->post(route('complaints.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error', __('messages.flash.garage_access_denied'));
    }
}
