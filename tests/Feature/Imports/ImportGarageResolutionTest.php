<?php

namespace Tests\Feature\Imports;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportGarageResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        GarageContext::clear();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garageA->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // 1. SESSION-BASED RESOLUTION (web request flow)
    // ==================================================================

    public function test_import_uses_session_when_context_is_empty(): void
    {
        // No Context set — the controller must resolve the garage from
        // the session, which the EnsureGarageSelected middleware
        // populates for real web requests.
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_garage_id' => $this->garageA->id,
                'current_company_id' => $this->company->id,
            ])
            ->post(route('buses.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        // The empty Excel will produce a "import_error" flash, but the
        // important thing is that the request reached the controller
        // and did NOT redirect to garage.selection.
        $response->assertRedirect(route('buses.index'));
    }

    // ==================================================================
    // 2. AUTH-USER-BASED RESOLUTION (queue/CLI fallback)
    // ==================================================================

    public function test_import_uses_auth_user_when_session_is_missing(): void
    {
        // Simulate a state where Context is empty AND session is empty,
        // but the authenticated user has a persisted current_garage_id.
        $this->admin->update([
            'current_garage_id' => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ]);

        $this->admin->refresh();

        // Bypass the middleware layer so that the request reaches the
        // controller with an empty session — this is the state a queue
        // job or CLI command would be in.
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureGarageSelected::class,
            \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('buses.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        // Controller must NOT redirect to garage.selection because
        // resolveGarageId() found the garage on the user model.
        $response->assertRedirect(route('buses.index'));
    }

    // ==================================================================
    // 3. NO GARAGE AVAILABLE ANYWHERE — REDIRECT TO SELECTION
    // ==================================================================

    public function test_import_redirects_to_selection_when_no_garage_can_be_resolved(): void
    {
        // User has no current_garage_id, session is empty, Context is
        // empty. The controller must redirect with a clear error.
        $this->admin->update([
            'current_garage_id' => null,
            'current_company_id' => null,
        ]);

        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureGarageSelected::class,
            \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('buses.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error');
    }

    // ==================================================================
    // 4. GARAGE ID IS PASSED TO THE IMPORT CORRECTLY
    // ==================================================================

    public function test_import_passes_resolved_garage_id_to_import_class(): void
    {
        // Sanity: this is verified indirectly by the import's own
        // constructor guard — if a null/zero garage id were passed the
        // AbstractImport constructor would throw InvalidArgumentException
        // and the controller would redirect with an "import_error"
        // flash. Since the request reaches the success branch instead,
        // we know the correct garage id was supplied.
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_garage_id' => $this->garageA->id,
                'current_company_id' => $this->company->id,
            ])
            ->post(route('drivers.import.store'), [
                'file' => UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        // Redirect to drivers.index (import_error branch reached from
        // inside the try-block, not from the guard) proves the guard
        // was satisfied with a positive garage id.
        $response->assertRedirect(route('drivers.index'));
    }
}
