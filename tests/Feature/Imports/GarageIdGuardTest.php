<?php

namespace Tests\Feature\Imports;

use App\Imports\ComplaintsImport;
use App\Imports\WarehouseImport;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Garage;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageIdGuardTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garageA->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // 1. CONSTRUCTOR GUARD — COMPLAINTS IMPORT
    // ==================================================================

    public function test_complaints_import_rejects_zero_garage_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a valid garage id');

        new ComplaintsImport(0, $this->company->id);
    }

    public function test_complaints_import_rejects_negative_garage_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ComplaintsImport(-5, $this->company->id);
    }

    public function test_complaints_import_accepts_positive_garage_id(): void
    {
        $import = new ComplaintsImport($this->garageA->id, $this->company->id);

        $this->assertEquals($this->garageA->id, $import->garageId);
    }

    // ==================================================================
    // 2. CONSTRUCTOR GUARD — WAREHOUSE IMPORT
    // ==================================================================

    public function test_warehouse_import_rejects_zero_garage_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a valid garage id');

        new WarehouseImport(0, $this->company->id);
    }

    public function test_warehouse_import_rejects_negative_garage_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new WarehouseImport(-1);
    }

    // ==================================================================
    // 3. CROSS-TENANT PROTECTION — COMPLAINTS
    // ==================================================================

    public function test_complaints_import_only_sees_buses_in_its_own_garage(): void
    {
        // Bus exists ONLY in garage B. Import is for garage A.
        Bus::factory()->create([
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'dqn'        => 'CROSS-001',
        ]);

        $import = new ComplaintsImport($this->garageA->id, $this->company->id);

        $import->processRow([
            'dqn'            => 'CROSS-001',
            'yer'            => 'garage',
            'complaint_type' => 'breakdown',
            'complaints'     => 'Cross-tenant test',
            'status'         => 'pending',
        ], 2);

        // Must skip — DQN exists but in another garage.
        $this->assertEquals(0, $import->importedCount);
        $this->assertCount(1, $import->skipped);

        // No complaint created for garage A
        $this->assertEquals(0, Complaint::withoutGlobalScopes()->count());
    }

    public function test_complaints_import_only_deducts_stock_from_its_own_garage(): void
    {
        // Bus in garage A (the import's garage)
        Bus::factory()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'dqn'        => 'OWN-001',
        ]);

        // Same part code exists in BOTH garages with different quantities
        $warehouseA = Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'code'       => 'SHARED-CODE',
            'name'       => 'Garage A Part',
            'quantity'   => 10,
        ]);

        $warehouseB = Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'code'       => 'SHARED-CODE',
            'name'       => 'Garage B Part',
            'quantity'   => 50,
        ]);

        $import = new ComplaintsImport($this->garageA->id, $this->company->id);

        $import->processRow([
            'dqn'            => 'OWN-001',
            'yer'            => 'garage',
            'complaint_type' => 'breakdown',
            'complaints'     => 'Test',
            'status'         => 'pending',
            'part_code'      => 'SHARED-CODE',
            'used_quantity'  => 3,
        ], 2);

        // Garage A's stock was decremented: 10 - 3 = 7
        $this->assertEquals(7, $warehouseA->fresh()->quantity);

        // Garage B's stock is UNTOUCHED
        $this->assertEquals(
            50,
            $warehouseB->fresh()->quantity,
            'CRITICAL: garage B stock must not be affected'
        );
    }

    public function test_complaints_import_does_not_find_part_from_other_garage(): void
    {
        Bus::factory()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'dqn'        => 'OWN-002',
        ]);

        // Part exists ONLY in garage B
        Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'code'       => 'B-ONLY-001',
            'name'       => 'Garage B Only',
            'quantity'   => 100,
        ]);

        $import = new ComplaintsImport($this->garageA->id, $this->company->id);

        $import->processRow([
            'dqn'            => 'OWN-002',
            'yer'            => 'garage',
            'complaint_type' => 'breakdown',
            'complaints'     => 'Test',
            'status'         => 'pending',
            'part_code'      => 'B-ONLY-001',
            'used_quantity'  => 5,
        ], 2);

        // Must skip — part not in garage A
        $this->assertEquals(0, $import->importedCount);
        $this->assertCount(1, $import->skipped);

        // Garage B stock untouched
        $warehouseB = Warehouse::withoutGlobalScopes()
            ->where('code', 'B-ONLY-001')
            ->first();
        $this->assertEquals(100, $warehouseB->quantity);
    }

    // ==================================================================
    // 4. HTTP LEVEL — CONTROLLER GUARD
    // ==================================================================

        public function test_complaints_import_redirects_when_no_garage_in_session(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'user']);
        $admin->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);

        // Bypass the middleware guards so that the CONTROLLER-level
        // guard runs in isolation. In production, EnsureGarageSelected
        // and RoleMiddleware intercept such requests first; this test
        // verifies the controller's own defense-in-depth layer.
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureGarageSelected::class,
            \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('complaints.import.store'), [
                'file' => \Illuminate\Http\UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error');
    }

    public function test_warehouse_import_redirects_when_no_garage_in_session(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'user']);
        $admin->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);

        // Same reasoning as the complaints test above: isolate the
        // controller's guard by bypassing the middleware layer.
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureGarageSelected::class,
            \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['current_garage_id' => null])
            ->post(route('warehouses.import.store'), [
                'file' => \Illuminate\Http\UploadedFile::fake()->create('dummy.xlsx', 100),
            ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error');
    }

    // ==================================================================
    // 5. WAREHOUSE IMPORT — CROSS-TENANT PROTECTION
    // ==================================================================

    public function test_warehouse_import_does_not_overwrite_other_garage_items(): void
    {
        // Same code in both garages, different quantities
        $warehouseA = Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'code'       => 'SHARED-001',
            'name'       => 'Garage A Item',
            'quantity'   => 10,
        ]);

        $warehouseB = Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'code'       => 'SHARED-001',
            'name'       => 'Garage B Item',
            'quantity'   => 50,
        ]);

        $import = new WarehouseImport($this->garageA->id, $this->company->id);

        $rows = collect([
            collect([
                'code'     => 'SHARED-001',
                'name'     => 'Updated Garage A',
                'quantity' => 99,
            ]),
        ]);

        $import->collection($rows);

        // Garage A updated
        $this->assertEquals('Updated Garage A', $warehouseA->fresh()->name);
        $this->assertEquals(99, $warehouseA->fresh()->quantity);

        // Garage B untouched
        $this->assertEquals('Garage B Item', $warehouseB->fresh()->name);
        $this->assertEquals(50, $warehouseB->fresh()->quantity);
    }
}
