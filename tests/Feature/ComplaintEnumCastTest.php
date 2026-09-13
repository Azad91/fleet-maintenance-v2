<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Enums\Location;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Garage;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintEnumCastTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function makeComplaint(array $overrides = []): Complaint
    {
        return Complaint::create(array_merge([
            'bus_id'         => $this->bus->id,
            'garage_id'      => $this->garage->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
        ], $overrides));
    }

    // ==================================================================
    // 1. STATUS CAST
    // ==================================================================

    public function test_status_is_cast_to_enum(): void
    {
        $complaint = $this->makeComplaint(['status' => 'pending']);

        $this->assertInstanceOf(ComplaintStatus::class, $complaint->status);
        $this->assertSame(ComplaintStatus::Pending, $complaint->status);
    }

    public function test_complaint_type_is_cast_to_enum(): void
    {
        $complaint = $this->makeComplaint(['complaint_type' => 'breakdown']);

        $this->assertInstanceOf(ComplaintType::class, $complaint->complaint_type);
        $this->assertSame(ComplaintType::Breakdown, $complaint->complaint_type);
    }

    public function test_yer_is_cast_to_enum(): void
    {
        $complaint = $this->makeComplaint(['yer' => 'garage']);

        $this->assertInstanceOf(Location::class, $complaint->yer);
        $this->assertSame(Location::Garage, $complaint->yer);
    }

    public function test_nullable_complaint_type_casts_to_null(): void
    {
        $complaint = $this->makeComplaint(['complaint_type' => null]);

        $this->assertNull($complaint->complaint_type);
    }

    // ==================================================================
    // 2. WRITE ACCEPTS STRING OR ENUM
    // ==================================================================

    public function test_update_accepts_enum_case(): void
    {
        $complaint = $this->makeComplaint();

        $complaint->update(['status' => ComplaintStatus::InProgress]);

        $this->assertSame(ComplaintStatus::InProgress, $complaint->fresh()->status);
        $this->assertDatabaseHas('complaints', [
            'id'     => $complaint->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_update_accepts_string_value(): void
    {
        $complaint = $this->makeComplaint();

        $complaint->update(['status' => 'in_progress']);

        $this->assertSame(ComplaintStatus::InProgress, $complaint->fresh()->status);
    }

    // ==================================================================
    // 3. ENUM HELPERS
    // ==================================================================

    public function test_status_label_resolves(): void
    {
        $complaint = $this->makeComplaint(['status' => 'pending']);

        $this->assertNotSame(
            'enums.complaint_status.pending',
            $complaint->status->label(),
            'Label should resolve via lang file, not return the raw key'
        );
    }

    public function test_status_bootstrap_color_is_valid(): void
    {
        $validColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

        foreach (ComplaintStatus::cases() as $case) {
            $this->assertContains($case->bootstrapColor(), $validColors);
        }
    }

    public function test_status_css_modifier_replaces_underscore(): void
    {
        $this->assertSame('in-progress', ComplaintStatus::InProgress->cssModifier());
        $this->assertSame('pending', ComplaintStatus::Pending->cssModifier());
    }

    // ==================================================================
    // 4. SCOPES STILL WORK WITH ENUM CAST
    // ==================================================================

    public function test_scope_open_returns_pending_and_in_progress(): void
    {
        $this->makeComplaint(['status' => 'pending']);
        $this->makeComplaint(['status' => 'in_progress']);
        $this->makeComplaint(['status' => 'completed']);
        $this->makeComplaint(['status' => 'cancelled']);

        $this->assertSame(2, Complaint::open()->count());
    }

    public function test_scope_closed_returns_completed_and_cancelled(): void
    {
        $this->makeComplaint(['status' => 'pending']);
        $this->makeComplaint(['status' => 'completed']);
        $this->makeComplaint(['status' => 'cancelled']);

        $this->assertSame(2, Complaint::closed()->count());
    }

    public function test_is_open_accessor_works_with_enum(): void
    {
        $pending    = $this->makeComplaint(['status' => 'pending']);
        $completed  = $this->makeComplaint(['status' => 'completed']);
        $cancelled  = $this->makeComplaint(['status' => 'cancelled']);

        $this->assertTrue($pending->is_open);
        $this->assertFalse($completed->is_open);
        $this->assertFalse($cancelled->is_open);
    }

    // ==================================================================
    // 5. AUDIT LOG STILL RECORDS RAW STRINGS
    // ==================================================================

    public function test_audit_log_records_raw_status_string(): void
    {
        $complaint = $this->makeComplaint(['status' => 'pending']);

        \App\Models\AuditLog::query()->delete();

        $complaint->update(['status' => ComplaintStatus::Completed]);

        $log = \App\Models\AuditLog::where('auditable_type', Complaint::class)
            ->where('auditable_id', $complaint->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('pending', $log->old_values['status']);
        $this->assertSame('completed', $log->new_values['status']);
    }
}
