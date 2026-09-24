<?php

namespace Tests\Feature;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\GarageContext;
use App\Services\Warehouse\WarehouseTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WarehouseTransferTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected User $adminA;

    protected User $adminB;

    protected Warehouse $itemA;

    protected WarehouseTransferService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->adminA = User::factory()->create(['role' => 'user']);
        $this->adminA->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);

        $this->adminB = User::factory()->create(['role' => 'user']);
        $this->adminB->garages()->attach($this->garageB->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($this->garageA->id, $this->company->id);

        $this->itemA = Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'code' => 'FILTER-001',
            'name' => 'Oil Filter',
            'quantity' => 100,
            'minimum_quantity' => 10,
            'unit' => 'piece',
        ]);

        $this->service = app(WarehouseTransferService::class);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function sessionFor(Garage $garage): array
    {
        return [
            'current_garage_id' => $garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    protected function makeTransfer(int $quantity = 5): WarehouseTransfer
    {
        return $this->service->create([
            'from_garage_id' => $this->garageA->id,
            'to_garage_id' => $this->garageB->id,
            'type' => TransferType::GarageToGarage->value,
            'notes' => 'Test transfer',
            'items' => [
                [
                    'warehouse_id' => $this->itemA->id,
                    'declared_quantity' => $quantity,
                ],
            ],
        ], $this->company->id);
    }

    // ==================================================================
    // 1. HAPPY PATH
    // ==================================================================

    public function test_draft_transfer_does_not_change_stock(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);

        $this->assertSame(TransferStatus::Draft, $transfer->status);
        $this->assertSame(100, $this->itemA->fresh()->quantity);
    }

    public function test_dispatch_decreases_source_stock(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);

        $this->service->dispatch($transfer);

        $this->assertSame(95, $this->itemA->fresh()->quantity);
        $this->assertSame(TransferStatus::Dispatched, $transfer->fresh()->status);
    }

    public function test_receive_with_matching_quantity_completes_transfer(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);
        $this->service->dispatch($transfer);

        $this->actingAs($this->adminB);
        $item = $transfer->items->first();

        $this->service->receive($transfer, [$item->id => 5]);

        $fresh = $transfer->fresh();
        $this->assertSame(TransferStatus::Received, $fresh->status);
        $this->assertSame(5, $fresh->received_total);

        // Destination warehouse should be auto-created with same code
        $destItem = Warehouse::withoutGlobalScopes()
            ->where('garage_id', $this->garageB->id)
            ->where('code', 'FILTER-001')
            ->first();

        $this->assertNotNull($destItem);
        $this->assertSame(5, $destItem->quantity);
    }

    public function test_receive_with_less_quantity_marks_as_disputed(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);
        $this->service->dispatch($transfer);

        $this->actingAs($this->adminB);
        $item = $transfer->items->first();

        $this->service->receive($transfer, [$item->id => 3]);

        $fresh = $transfer->fresh();
        $this->assertSame(TransferStatus::Disputed, $fresh->status);
        $this->assertSame(3, $fresh->received_total);

        // Destination stock should only be +3
        $destItem = Warehouse::withoutGlobalScopes()
            ->where('garage_id', $this->garageB->id)
            ->where('code', 'FILTER-001')
            ->first();

        $this->assertSame(3, $destItem->quantity);
    }

    // ==================================================================
    // 2. GUARDS
    // ==================================================================

    public function test_cannot_dispatch_transfer_with_insufficient_stock(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(150); // > 100 available

        $this->expectException(ValidationException::class);
        $this->service->dispatch($transfer);
    }

    public function test_cannot_dispatch_already_dispatched_transfer(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);
        $this->service->dispatch($transfer);

        $this->expectException(ValidationException::class);
        $this->service->dispatch($transfer->fresh());
    }

    public function test_cannot_receive_draft_transfer(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);

        $this->expectException(ValidationException::class);
        $this->service->receive($transfer, [$transfer->items->first()->id => 5]);
    }

    public function test_cannot_cancel_dispatched_transfer(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);
        $this->service->dispatch($transfer);

        $this->expectException(ValidationException::class);
        $this->service->cancel($transfer->fresh());
    }

    // ==================================================================
    // 3. REJECT
    // ==================================================================

    public function test_reject_restores_source_stock(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);
        $this->service->dispatch($transfer);

        $this->assertSame(95, $this->itemA->fresh()->quantity);

        $this->actingAs($this->adminB);
        $this->service->reject($transfer, 'Damaged in transit');

        $this->assertSame(100, $this->itemA->fresh()->quantity);
        $this->assertSame(TransferStatus::Rejected, $transfer->fresh()->status);
    }

    // ==================================================================
    // 4. RESOLVE DISPUTED
    // ==================================================================

    public function test_resolve_with_retransfer_creates_follow_up_draft(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(10);
        $this->service->dispatch($transfer);

        $this->actingAs($this->adminB);
        $item = $transfer->items->first();
        $this->service->receive($transfer, [$item->id => 6]);

        $this->actingAs($this->adminA);
        $this->service->resolveDisputed($transfer->fresh(), 'retransfer');

        $this->assertSame(TransferStatus::Resolved, $transfer->fresh()->status);
        $this->assertSame('retransfer', $transfer->fresh()->resolution);

        // A new draft should exist for the missing 4
        $followUp = WarehouseTransfer::where('id', '!=', $transfer->id)
            ->where('from_garage_id', $this->garageA->id)
            ->where('status', TransferStatus::Draft->value)
            ->first();

        $this->assertNotNull($followUp);
        $this->assertSame(4, $followUp->declared_total);
    }

    public function test_resolve_with_loss_accepted_marks_resolved(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(10);
        $this->service->dispatch($transfer);

        $this->actingAs($this->adminB);
        $this->service->receive($transfer, [$transfer->items->first()->id => 6]);

        $this->actingAs($this->adminA);
        $this->service->resolveDisputed($transfer->fresh(), 'loss_accepted');

        $this->assertSame(TransferStatus::Resolved, $transfer->fresh()->status);
        $this->assertSame('loss_accepted', $transfer->fresh()->resolution);

        // No follow-up transfer should exist
        $count = WarehouseTransfer::where('from_garage_id', $this->garageA->id)->count();
        $this->assertSame(1, $count);
    }

    // ==================================================================
    // 5. POLICY / HTTP LAYER
    // ==================================================================

    public function test_source_admin_can_dispatch(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);

        $response = $this->withSession($this->sessionFor($this->garageA))
            ->post(route('warehouse-transfers.dispatch', $transfer));

        $response->assertRedirect(route('warehouse-transfers.show', $transfer));
        $this->assertSame(TransferStatus::Dispatched, $transfer->fresh()->status);
    }

    public function test_destination_admin_can_receive(): void
    {
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);
        $this->service->dispatch($transfer);
        $item = $transfer->items->first();

        $this->actingAs($this->adminB);
        $response = $this->withSession($this->sessionFor($this->garageB))
            ->post(route('warehouse-transfers.receive', $transfer), [
                'received' => [$item->id => 5],
            ]);

        $response->assertRedirect(route('warehouse-transfers.show', $transfer));
        $this->assertSame(TransferStatus::Received, $transfer->fresh()->status);
    }

    public function test_admin_of_unrelated_garage_cannot_view_transfer(): void
    {
        $garageC = Garage::factory()->create(['company_id' => $this->company->id]);
        $adminC = User::factory()->create(['role' => 'user']);
        $adminC->garages()->attach($garageC->id, ['role' => 'admin', 'is_active' => true]);

        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);

        $this->actingAs($adminC);
        $this->withSession($this->sessionFor($garageC))
            ->get(route('warehouse-transfers.show', $transfer))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_access_transfers(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garageA->id, ['role' => 'complaint_worker', 'is_active' => true]);

        $this->actingAs($worker)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('warehouse-transfers.index'))
            ->assertForbidden();
    }

    // ==================================================================
    // 6. INDEX VISIBILITY
    // ==================================================================

    public function test_index_shows_only_transfers_visible_to_current_garage(): void
    {
        // Transfer A → B
        $this->actingAs($this->adminA);
        $this->makeTransfer(5);

        // Transfer from C (unrelated)
        $garageC = Garage::factory()->create(['company_id' => $this->company->id]);
        $itemC = Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $garageC->id,
            'company_id' => $this->company->id,
            'code' => 'X-001',
            'name' => 'X',
            'quantity' => 10,
        ]);
        $this->service->create([
            'from_garage_id' => $garageC->id,
            'to_garage_id' => $this->garageB->id,
            'type' => TransferType::GarageToGarage->value,
            'items' => [['warehouse_id' => $itemC->id, 'declared_quantity' => 1]],
        ], $this->company->id);

        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('warehouse-transfers.index'));

        $response->assertOk();

        // Count via the view-bound variable
        $response->assertViewHas('transfers', function ($paginator) {
            return $paginator->total() === 1; // only the A→B one
        });
    }
    // ==================================================================
    // 7. QUARANTINE (RETURN_TO_QUARANTINE)
    // ==================================================================

    public function test_quarantine_creates_new_row_with_q_prefix(): void
    {
        $this->actingAs($this->adminA);

        $transfer = $this->service->createAndComplete([
            'from_garage_id' => $this->garageA->id,
            'type' => TransferType::ReturnToQuarantine->value,
            'items' => [
                ['warehouse_id' => $this->itemA->id, 'declared_quantity' => 5],
            ],
        ], $this->company->id);

        // Transfer immediately received
        $this->assertSame(TransferStatus::Received, $transfer->status);

        // Active stock decreased
        $this->assertSame(95, $this->itemA->fresh()->quantity);

        // Quarantine row created with Q- prefix
        $quarantine = Warehouse::withoutGlobalScopes()
            ->where('garage_id', $this->garageA->id)
            ->where('code', 'Q-FILTER-001')
            ->first();

        $this->assertNotNull($quarantine);
        $this->assertTrue($quarantine->is_quarantine);
        $this->assertSame(5, $quarantine->quantity);
    }

    public function test_quarantine_accumulates_in_existing_q_row(): void
    {
        $this->actingAs($this->adminA);

        // Two separate quarantine operations
        $this->service->createAndComplete([
            'from_garage_id' => $this->garageA->id,
            'type' => TransferType::ReturnToQuarantine->value,
            'items' => [['warehouse_id' => $this->itemA->id, 'declared_quantity' => 3]],
        ], $this->company->id);

        $this->service->createAndComplete([
            'from_garage_id' => $this->garageA->id,
            'type' => TransferType::ReturnToQuarantine->value,
            'items' => [['warehouse_id' => $this->itemA->id, 'declared_quantity' => 4]],
        ], $this->company->id);

        $quarantine = Warehouse::withoutGlobalScopes()
            ->where('garage_id', $this->garageA->id)
            ->where('code', 'Q-FILTER-001')
            ->first();

        $this->assertSame(7, $quarantine->quantity);
        $this->assertSame(93, $this->itemA->fresh()->quantity);
    }

    public function test_quarantine_rejects_insufficient_stock(): void
    {
        $this->actingAs($this->adminA);

        $this->expectException(ValidationException::class);

        $this->service->createAndComplete([
            'from_garage_id' => $this->garageA->id,
            'type' => TransferType::ReturnToQuarantine->value,
            'items' => [['warehouse_id' => $this->itemA->id, 'declared_quantity' => 150]],
        ], $this->company->id);
    }

    public function test_quarantine_cannot_use_already_quarantined_item_as_source(): void
    {
        $this->actingAs($this->adminA);

        // First quarantine to create the Q- row
        $this->service->createAndComplete([
            'from_garage_id' => $this->garageA->id,
            'type' => TransferType::ReturnToQuarantine->value,
            'items' => [['warehouse_id' => $this->itemA->id, 'declared_quantity' => 5]],
        ], $this->company->id);

        $quarantine = Warehouse::withoutGlobalScopes()
            ->where('garage_id', $this->garageA->id)
            ->where('code', 'Q-FILTER-001')
            ->first();

        // Now try to quarantine the already-quarantined item
        $this->expectException(ValidationException::class);

        $this->service->createAndComplete([
            'from_garage_id' => $this->garageA->id,
            'type' => TransferType::ReturnToQuarantine->value,
            'items' => [['warehouse_id' => $quarantine->id, 'declared_quantity' => 2]],
        ], $this->company->id);
    }

    public function test_http_quarantine_redirects_to_show_page(): void
    {
        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->post(route('warehouse-transfers.store'), [
                'type' => TransferType::ReturnToQuarantine->value,
                'notes' => 'Broken filters found in stock',
                'items' => [
                    ['warehouse_id' => $this->itemA->id, 'declared_quantity' => 2],
                ],
            ]);

        $transfer = WarehouseTransfer::latest('id')->first();

        $response->assertRedirect(route('warehouse-transfers.show', $transfer));
        $this->assertSame(TransferStatus::Received, $transfer->status);
    }

    public function test_warehouse_index_hides_quarantine_by_default(): void
    {
        $this->actingAs($this->adminA);

        // Create a quarantine row via the service
        $this->service->createAndComplete([
            'from_garage_id' => $this->garageA->id,
            'type' => TransferType::ReturnToQuarantine->value,
            'items' => [['warehouse_id' => $this->itemA->id, 'declared_quantity' => 5]],
        ], $this->company->id);

        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('warehouses.index'));

        $response->assertOk();
        $response->assertDontSee('Q-FILTER-001');
    }

    public function test_warehouse_index_shows_quarantine_when_requested(): void
    {
        $this->actingAs($this->adminA);

        $this->service->createAndComplete([
            'from_garage_id' => $this->garageA->id,
            'type' => TransferType::ReturnToQuarantine->value,
            'items' => [['warehouse_id' => $this->itemA->id, 'declared_quantity' => 5]],
        ], $this->company->id);

        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('warehouses.index', ['view' => 'quarantine']));

        $response->assertOk();
        $response->assertSee('Q-FILTER-001');
    }
    // ==================================================================
    // 7. CROSS-COMPANY SECURITY (P0 FIX)
    // ==================================================================

    public function test_cannot_transfer_to_garage_from_another_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherGarage = Garage::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->post(route('warehouse-transfers.store'), [
                'type' => TransferType::GarageToGarage->value,
                'to_garage_id' => $otherGarage->id, // ← other company's garage
                'items' => [
                    ['warehouse_id' => $this->itemA->id, 'declared_quantity' => 5],
                ],
            ])
            ->assertSessionHasErrors('to_garage_id');

        // Heç bir transfer yaranmamalıdır
        $this->assertSame(
            0,
            WarehouseTransfer::where('to_garage_id', $otherGarage->id)->count(),
            'Cross-company transfer must be rejected at the request layer'
        );
    }

    public function test_service_layer_rejects_cross_company_destination(): void
    {
        // Defense-in-depth test: bypass the FormRequest and call the
        // service directly. This simulates a future controller, queue
        // job, or console command that forgets to validate.
        $otherCompany = Company::factory()->create();
        $otherGarage = Garage::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($this->adminA);

        $this->expectException(ValidationException::class);

        $this->service->create([
            'from_garage_id' => $this->garageA->id,
            'to_garage_id' => $otherGarage->id, // ← other company's garage
            'type' => TransferType::GarageToGarage->value,
            'items' => [
                ['warehouse_id' => $this->itemA->id, 'declared_quantity' => 5],
            ],
        ], $this->company->id);

        // Heç bir warehouse sətri yaranmamalıdır
        $this->assertSame(
            0,
            Warehouse::withoutGlobalScopes()
                ->where('garage_id', $otherGarage->id)
                ->where('code', 'FILTER-001')
                ->count(),
            'Cross-company stock injection must be blocked at the service layer'
        );
    }

    public function test_destination_warehouse_row_uses_destination_company_id(): void
    {
        // Even if the guard passes (same company), the new destination
        // warehouse row must carry the DESTINATION garage's company_id.
        $this->actingAs($this->adminA);
        $transfer = $this->makeTransfer(5);
        $this->service->dispatch($transfer);

        $this->actingAs($this->adminB);
        $item = $transfer->items->first();
        $this->service->receive($transfer, [$item->id => 5]);

        $destRow = Warehouse::withoutGlobalScopes()
            ->where('garage_id', $this->garageB->id)
            ->where('code', 'FILTER-001')
            ->first();

        $this->assertNotNull($destRow);
        $this->assertSame(
            $this->company->id,
            $destRow->company_id,
            'Destination warehouse row must carry the destination garage\'s company_id'
        );
    }

    public function test_cannot_transfer_to_service_vehicle_from_another_garage(): void
    {
        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);
        $otherVehicle = \App\Models\ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $otherGarage->id,
            'company_id' => $this->company->id,
            'name' => 'Other Garage Vehicle',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->post(route('warehouse-transfers.store'), [
                'type' => TransferType::ToServiceVehicle->value,
                'to_service_vehicle_id' => $otherVehicle->id, // ← other garage
                'items' => [
                    ['warehouse_id' => $this->itemA->id, 'declared_quantity' => 5],
                ],
            ])
            ->assertSessionHasErrors('to_service_vehicle_id');
    }
}
