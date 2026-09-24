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

/**
 * Regression guard for the surplus-inventory bug.
 *
 * Before the fix, receive() only validated `received >= 0`. A destination
 * could declare it received MORE units than the source dispatched,
 * and the destination stock would be credited with the higher number.
 * Since dispatch() already deducted exactly `declared` from the source,
 * the difference appeared as stock created out of thin air.
 *
 * The fix rejects any `received > declared` at both the FormRequest
 * layer AND the service layer (defense-in-depth for callers that
 * bypass HTTP — queue jobs, CLI, tests).
 */
class WarehouseTransferSurplusTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected User $adminA;

    protected User $adminB;

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

        $this->service = app(WarehouseTransferService::class);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    protected function makeWarehouse(Garage $garage, string $code = 'W-001', int $qty = 100): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $garage->id,
            'company_id' => $this->company->id,
            'code' => $code,
            'name' => "Part {$code}",
            'quantity' => $qty,
        ]);
    }

    protected function makeDispatchedTransfer(int $declared = 5): WarehouseTransfer
    {
        $source = $this->makeWarehouse($this->garageA, 'W-001', 100);

        $this->actingAs($this->adminA);

        $transfer = $this->service->create([
            'from_garage_id' => $this->garageA->id,
            'to_garage_id' => $this->garageB->id,
            'type' => TransferType::GarageToGarage->value,
            'items' => [['warehouse_id' => $source->id, 'declared_quantity' => $declared]],
        ], $this->company->id);

        $this->service->dispatch($transfer);

        return $transfer->fresh();
    }

    // ==================================================================
    // 1. SERVICE LAYER — surplus is rejected
    // ==================================================================

    public function test_receive_rejects_more_than_declared(): void
    {
        $transfer = $this->makeDispatchedTransfer(declared: 5);
        $itemId = $transfer->items->first()->id;

        $this->actingAs($this->adminB);

        $this->expectException(ValidationException::class);

        $this->service->receive($transfer, [$itemId => 7]);
    }

    public function test_receive_rejects_surplus_and_leaves_stock_unchanged(): void
    {
        $transfer = $this->makeDispatchedTransfer(declared: 5);
        $itemId = $transfer->items->first()->id;

        $this->actingAs($this->adminB);

        try {
            $this->service->receive($transfer, [$itemId => 7]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            // expected
        }

        // Source is already 100 - 5 = 95 (dispatch).
        $this->assertSame(
            95,
            Warehouse::withoutGlobalScopes()
                ->where('code', 'W-001')
                ->where('garage_id', $this->garageA->id)
                ->value('quantity')
        );

        // Destination must NOT have a new row — surplus would create one.
        $this->assertSame(
            0,
            Warehouse::withoutGlobalScopes()
                ->where('code', 'W-001')
                ->where('garage_id', $this->garageB->id)
                ->count()
        );

        // Transfer must still be in "dispatched" state — nothing was written.
        $this->assertSame(
            TransferStatus::Dispatched,
            $transfer->fresh()->status
        );
    }

    // ==================================================================
    // 2. HAPPY PATH — received == declared still works
    // ==================================================================

    public function test_receive_with_exact_quantity_still_succeeds(): void
    {
        $transfer = $this->makeDispatchedTransfer(declared: 5);
        $itemId = $transfer->items->first()->id;

        $this->actingAs($this->adminB);

        $this->service->receive($transfer, [$itemId => 5]);

        $this->assertSame(
            TransferStatus::Received,
            $transfer->fresh()->status
        );

        // Destination got exactly 5
        $this->assertSame(
            5,
            Warehouse::withoutGlobalScopes()
                ->where('code', 'W-001')
                ->where('garage_id', $this->garageB->id)
                ->value('quantity')
        );
    }

    // ==================================================================
    // 3. UNDER-RECEIVE still marks as disputed (regression)
    // ==================================================================

    public function test_receive_with_less_than_declared_still_marks_disputed(): void
    {
        $transfer = $this->makeDispatchedTransfer(declared: 5);
        $itemId = $transfer->items->first()->id;

        $this->actingAs($this->adminB);

        $this->service->receive($transfer, [$itemId => 3]);

        $this->assertSame(
            TransferStatus::Disputed,
            $transfer->fresh()->status
        );

        // Destination got 3
        $this->assertSame(
            3,
            Warehouse::withoutGlobalScopes()
                ->where('code', 'W-001')
                ->where('garage_id', $this->garageB->id)
                ->value('quantity')
        );
    }

    // ==================================================================
    // 4. ZERO received still allowed (missing item)
    // ==================================================================

    public function test_receive_with_zero_quantity_is_allowed(): void
    {
        $transfer = $this->makeDispatchedTransfer(declared: 5);
        $itemId = $transfer->items->first()->id;

        $this->actingAs($this->adminB);

        $this->service->receive($transfer, [$itemId => 0]);

        $this->assertSame(
            TransferStatus::Disputed,
            $transfer->fresh()->status
        );

        // Destination has no row — nothing was credited.
        $this->assertSame(
            0,
            Warehouse::withoutGlobalScopes()
                ->where('code', 'W-001')
                ->where('garage_id', $this->garageB->id)
                ->count()
        );
    }

    // ==================================================================
    // 5. HTTP LAYER — FormRequest rejects surplus
    // ==================================================================

    public function test_http_receive_rejects_surplus_with_validation_error(): void
    {
        $transfer = $this->makeDispatchedTransfer(declared: 5);
        $itemId = $transfer->items->first()->id;

        $response = $this->actingAs($this->adminB)
            ->withSession([
                'current_garage_id' => $this->garageB->id,
                'current_company_id' => $this->company->id,
            ])
            ->post(route('warehouse-transfers.receive', $transfer), [
                'received' => [$itemId => 7],
            ]);

        $response->assertSessionHasErrors("received.{$itemId}");

        // Transfer stays in dispatched state — no partial write.
        $this->assertSame(
            TransferStatus::Dispatched,
            $transfer->fresh()->status
        );
    }

    public function test_http_receive_accepts_exact_quantity(): void
    {
        $transfer = $this->makeDispatchedTransfer(declared: 5);
        $itemId = $transfer->items->first()->id;

        $response = $this->actingAs($this->adminB)
            ->withSession([
                'current_garage_id' => $this->garageB->id,
                'current_company_id' => $this->company->id,
            ])
            ->post(route('warehouse-transfers.receive', $transfer), [
                'received' => [$itemId => 5],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(
            TransferStatus::Received,
            $transfer->fresh()->status
        );
    }
}
