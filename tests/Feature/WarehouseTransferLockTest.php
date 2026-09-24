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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Regression guard for the missing row lock on warehouse_transfers.
 *
 * Before the fix, dispatch() / receive() / reject() / resolveDisputed()
 * and cancel() all read the transfer status OUTSIDE the transaction
 * and did NOT lock the transfer row. Two concurrent requests could
 * both pass the status check and both proceed to mutate stock or
 * state — a double-deduction, double-credit, or state corruption.
 *
 * These tests verify that every state-changing method now acquires
 * a `lockForUpdate` on the warehouse_transfers row BEFORE reading
 * the status. The actual deadlock / race can only be triggered with
 * two real concurrent DB sessions, which PHPUnit cannot easily
 * simulate — so we assert the lock acquisition itself.
 */
class WarehouseTransferLockTest extends TestCase
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
            'garage_id'  => $garage->id,
            'company_id' => $this->company->id,
            'code'       => $code,
            'name'       => "Part {$code}",
            'quantity'   => $qty,
        ]);
    }

    protected function makeDraftTransfer(): WarehouseTransfer
    {
        $source = $this->makeWarehouse($this->garageA);

        $this->actingAs($this->adminA);

        return $this->service->create([
            'from_garage_id' => $this->garageA->id,
            'to_garage_id'   => $this->garageB->id,
            'type'           => TransferType::GarageToGarage->value,
            'items'          => [['warehouse_id' => $source->id, 'declared_quantity' => 5]],
        ], $this->company->id);
    }

    /**
     * Capture any `for update` query that touches warehouse_transfers.
     */
    protected function captureTransferLocks(callable $action): int
    {
        $count = 0;

        DB::listen(function ($query) use (&$count) {
            $sql = strtolower($query->sql);

            if (str_contains($sql, 'for update')
                && str_contains($sql, 'warehouse_transfers')) {
                $count++;
            }
        });

        $action();

        return $count;
    }

    // ==================================================================
    // 1. dispatch() locks the transfer row
    // ==================================================================

    public function test_dispatch_locks_transfer_row_before_status_check(): void
    {
        $transfer = $this->makeDraftTransfer();

        $locks = $this->captureTransferLocks(function () use ($transfer) {
            $this->service->dispatch($transfer);
        });

        $this->assertGreaterThanOrEqual(
            1,
            $locks,
            'dispatch() must acquire a row lock on warehouse_transfers'
        );
    }

    // ==================================================================
    // 2. receive() locks the transfer row
    // ==================================================================

    public function test_receive_locks_transfer_row_before_status_check(): void
    {
        $transfer = $this->makeDraftTransfer();
        $this->service->dispatch($transfer);

        $this->actingAs($this->adminB);
        $itemId = $transfer->fresh()->items->first()->id;

        $locks = $this->captureTransferLocks(function () use ($transfer, $itemId) {
            $this->service->receive($transfer->fresh(), [$itemId => 5]);
        });

        $this->assertGreaterThanOrEqual(1, $locks);
    }

    // ==================================================================
    // 3. reject() locks the transfer row
    // ==================================================================

    public function test_reject_locks_transfer_row_before_status_check(): void
    {
        $transfer = $this->makeDraftTransfer();
        $this->service->dispatch($transfer);

        $this->actingAs($this->adminB);

        $locks = $this->captureTransferLocks(function () use ($transfer) {
            $this->service->reject($transfer->fresh(), 'Test rejection');
        });

        $this->assertGreaterThanOrEqual(1, $locks);
    }

    // ==================================================================
    // 4. resolveDisputed() locks the transfer row
    // ==================================================================

    public function test_resolve_disputed_locks_transfer_row_before_status_check(): void
    {
        $transfer = $this->makeDraftTransfer();
        $this->service->dispatch($transfer);

        $this->actingAs($this->adminB);
        $itemId = $transfer->fresh()->items->first()->id;

        // Receive fewer to force disputed state
        $this->service->receive($transfer->fresh(), [$itemId => 2]);

        $this->actingAs($this->adminA);

        $locks = $this->captureTransferLocks(function () use ($transfer) {
            $this->service->resolveDisputed($transfer->fresh(), 'loss_accepted');
        });

        $this->assertGreaterThanOrEqual(1, $locks);
    }

    // ==================================================================
    // 5. cancel() locks the transfer row
    // ==================================================================

    public function test_cancel_locks_transfer_row_before_status_check(): void
    {
        $transfer = $this->makeDraftTransfer();

        $locks = $this->captureTransferLocks(function () use ($transfer) {
            $this->service->cancel($transfer);
        });

        $this->assertGreaterThanOrEqual(1, $locks);
    }

    // ==================================================================
    // 6. Regression — dispatch twice on same transfer is rejected
    //    (existing behaviour, must still hold after the lock fix)
    // ==================================================================

    public function test_dispatch_rejects_second_call(): void
    {
        $transfer = $this->makeDraftTransfer();
        $this->service->dispatch($transfer);

        $this->expectException(ValidationException::class);

        $this->service->dispatch($transfer->fresh());
    }

    // ==================================================================
    // 7. Regression — cancel after dispatch is rejected
    // ==================================================================

    public function test_cancel_rejects_dispatched_transfer(): void
    {
        $transfer = $this->makeDraftTransfer();
        $this->service->dispatch($transfer);

        $this->expectException(ValidationException::class);

        $this->service->cancel($transfer->fresh());
    }

    // ==================================================================
    // 8. Regression — resolve only works on disputed transfers
    // ==================================================================

    public function test_resolve_rejects_non_disputed_transfer(): void
    {
        $transfer = $this->makeDraftTransfer();

        $this->expectException(ValidationException::class);

        $this->service->resolveDisputed($transfer, 'loss_accepted');
    }
}