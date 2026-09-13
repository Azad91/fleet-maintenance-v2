<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditableTypeComparisonTest extends TestCase
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

    private function createBus(array $attributes = []): Bus
    {
        return Bus::factory()->create(array_merge([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'is_active'  => true,
        ], $attributes));
    }

    // ==================================================================
    // 1. NORMALIZATION — EQUIVALENT VALUES NOT DETECTED AS CHANGE
    // ==================================================================

    public function test_int_and_string_int_are_considered_equal(): void
    {
        $bus = $this->createBus(['is_active' => true]);

        AuditLog::query()->delete();

        // Simulate bulk update with the SAME value using a different type.
        // Old value in DB may come as bool true or int 1 depending on driver;
        // new value here is PHP int 1.
        Bus::auditBulkUpdate([$bus->id], ['is_active' => 1], 'bulk_test');

        $this->assertEquals(
            0,
            AuditLog::where('auditable_id', $bus->id)->count(),
            'Equivalent value (int 1 vs stored bool) must NOT produce an audit log'
        );
    }

    public function test_boolean_true_and_string_1_are_considered_equal(): void
    {
        $bus = $this->createBus(['is_active' => true]);

        AuditLog::query()->delete();

        Bus::auditBulkUpdate([$bus->id], ['is_active' => '1'], 'bulk_test');

        $this->assertEquals(
            0,
            AuditLog::where('auditable_id', $bus->id)->count(),
            "Boolean true (DB) and string '1' must be considered equal"
        );
    }

    public function test_boolean_false_and_string_0_are_considered_equal(): void
    {
        $bus = $this->createBus(['is_active' => false]);

        AuditLog::query()->delete();

        Bus::auditBulkUpdate([$bus->id], ['is_active' => '0'], 'bulk_test');

        $this->assertEquals(
            0,
            AuditLog::where('auditable_id', $bus->id)->count(),
            "Boolean false (DB) and string '0' must be considered equal"
        );
    }

    public function test_null_and_null_are_considered_equal(): void
    {
        $bus = $this->createBus(['engine_number' => null]);

        AuditLog::query()->delete();

        Bus::auditBulkUpdate([$bus->id], ['engine_number' => null], 'bulk_test');

        $this->assertEquals(
            0,
            AuditLog::where('auditable_id', $bus->id)->count(),
            'null vs null must NOT produce an audit log'
        );
    }

    // ==================================================================
    // 2. GENUINE CHANGES ARE DETECTED
    // ==================================================================

    public function test_true_to_false_creates_audit_log(): void
    {
        $bus = $this->createBus(['is_active' => true]);

        AuditLog::query()->delete();

        Bus::auditBulkUpdate([$bus->id], ['is_active' => false], 'bulk_deactivated');

        $logs = AuditLog::where('auditable_id', $bus->id)->get();

        $this->assertCount(1, $logs, 'true → false must produce an audit log');
        $this->assertEquals('bulk_deactivated', $logs->first()->event);
    }

    public function test_false_to_true_creates_audit_log(): void
    {
        $bus = $this->createBus(['is_active' => false]);

        AuditLog::query()->delete();

        Bus::auditBulkUpdate([$bus->id], ['is_active' => true], 'bulk_activated');

        $logs = AuditLog::where('auditable_id', $bus->id)->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('bulk_activated', $logs->first()->event);
    }

    public function test_string_value_change_creates_audit_log(): void
    {
        $bus = $this->createBus(['engine_number' => 'ENG-001']);

        AuditLog::query()->delete();

        Bus::auditBulkUpdate([$bus->id], ['engine_number' => 'ENG-002'], 'bulk_updated');

        $logs = AuditLog::where('auditable_id', $bus->id)->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('ENG-001', $logs->first()->old_values['engine_number']);
        $this->assertEquals('ENG-002', $logs->first()->new_values['engine_number']);
    }

    // ==================================================================
    // 3. NULL vs NON-NULL — MUST BE DETECTED AS CHANGE
    // ==================================================================

    public function test_null_to_empty_string_creates_audit_log(): void
    {
        $bus = $this->createBus(['engine_number' => null]);

        AuditLog::query()->delete();

        Bus::auditBulkUpdate([$bus->id], ['engine_number' => ''], 'bulk_updated');

        $logs = AuditLog::where('auditable_id', $bus->id)->get();

        $this->assertCount(
            1,
            $logs,
            "null → '' is a genuine change (different semantics in DB)"
        );
    }

    public function test_null_to_zero_creates_audit_log(): void
    {
        $bus = $this->createBus(['km' => null]);

        AuditLog::query()->delete();

        Bus::auditBulkUpdate([$bus->id], ['km' => 0], 'bulk_updated');

        $logs = AuditLog::where('auditable_id', $bus->id)->get();

        $this->assertCount(1, $logs, 'null → 0 is a genuine change');
    }

    // ==================================================================
    // 4. MULTI-RECORD BULK UPDATE
    // ==================================================================

    public function test_bulk_update_skips_records_with_no_real_change(): void
    {
        $busA = $this->createBus(['is_active' => true]);
        $busB = $this->createBus(['is_active' => false]);

        AuditLog::query()->delete();

        // Activate both. BusA: no change (already active). BusB: real change.
        Bus::auditBulkUpdate([$busA->id, $busB->id], ['is_active' => true], 'bulk_activated');

        $this->assertEquals(
            0,
            AuditLog::where('auditable_id', $busA->id)->count(),
            'BusA was already active — no audit log expected'
        );

        $this->assertEquals(
            1,
            AuditLog::where('auditable_id', $busB->id)->count(),
            'BusB changed from inactive to active — audit log expected'
        );
    }

    public function test_bulk_update_creates_one_log_per_changed_record(): void
    {
        $busA = $this->createBus(['is_active' => true]);
        $busB = $this->createBus(['is_active' => true]);
        $busC = $this->createBus(['is_active' => true]);

        AuditLog::query()->delete();

        Bus::auditBulkUpdate([$busA->id, $busB->id, $busC->id], ['is_active' => false], 'bulk_deactivated');

        $this->assertEquals(3, AuditLog::count());
    }

    // ==================================================================
    // 5. FULL INTEGRATION VIA SERVICE
    // ==================================================================

    public function test_bus_service_bulk_update_creates_audit_logs_only_for_real_changes(): void
    {
        $activeBus = $this->createBus(['is_active' => true]);
        $inactiveBus = $this->createBus(['is_active' => false]);

        AuditLog::query()->delete();

        $service = app(\App\Services\BusService::class);
        $service->bulkUpdateStatus([$activeBus->id, $inactiveBus->id], true);

        $this->assertEquals(
            0,
            AuditLog::where('auditable_id', $activeBus->id)->count(),
            'Active bus was already active — no audit log'
        );

        $this->assertEquals(
            1,
            AuditLog::where('auditable_id', $inactiveBus->id)->count(),
            'Inactive bus was activated — one audit log'
        );

        $log = AuditLog::where('auditable_id', $inactiveBus->id)->first();
        $this->assertEquals('bulk_activated', $log->event);
    }
}
