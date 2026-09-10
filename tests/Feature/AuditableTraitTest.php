<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditableTraitTest extends TestCase
{
    use RefreshDatabase;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $company->id]);

        GarageContext::set($this->garage->id, $company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    public function test_created_event_writes_single_audit_log(): void
    {
        $bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->garage->company_id,
        ]);

        $logs = AuditLog::where('auditable_type', Bus::class)
            ->where('auditable_id', $bus->id)
            ->where('event', 'created')
            ->get();

        $this->assertCount(1, $logs, 'created event must write exactly 1 log');
    }

    public function test_updated_event_writes_single_audit_log(): void
    {
        $bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->garage->company_id,
        ]);

        AuditLog::query()->delete(); // remove created log

        $bus->update(['route_number' => 'TEST-999']);

        $logs = AuditLog::where('auditable_type', Bus::class)
            ->where('auditable_id', $bus->id)
            ->where('event', 'updated')
            ->get();

        $this->assertCount(1, $logs, 'updated event must write exactly 1 log (not 2)');

        $newValues = $logs->first()->new_values;

        $this->assertArrayHasKey('route_number', $newValues);
        $this->assertEquals('TEST-999', $newValues['route_number']);

        // Timestamps must be filtered out
        $this->assertArrayNotHasKey('updated_at', $newValues);
        $this->assertArrayNotHasKey('created_at', $newValues);
    }

    public function test_no_audit_log_when_nothing_changes(): void
    {
        $bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->garage->company_id,
        ]);

        AuditLog::query()->delete();

        // Set the same value — nothing changes
        $bus->update(['route_number' => $bus->route_number]);

        $logs = AuditLog::where('auditable_type', Bus::class)
            ->where('auditable_id', $bus->id)
            ->get();

        $this->assertCount(0, $logs, 'no change → no audit log');
    }

    public function test_deleted_event_writes_audit_log(): void
    {
        $bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->garage->company_id,
        ]);

        AuditLog::query()->delete();

        $bus->delete(); // soft delete

        $log = AuditLog::where('auditable_type', Bus::class)
            ->where('auditable_id', $bus->id)
            ->where('event', 'deleted')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_values);
        $this->assertNull($log->new_values);
    }
}