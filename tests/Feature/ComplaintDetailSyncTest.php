<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintDetail;
use App\Models\Employee;
use App\Models\Garage;
use App\Models\Warehouse;
use App\Services\Complaint\ComplaintItemService;
use App\Services\Complaint\ComplaintService;
use App\Services\Complaint\ComplaintStatusTransitionService;
use App\Services\Complaint\ComplaintStockService;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintDetailSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected Employee $employee;

    protected ComplaintService $service;

    protected ComplaintStockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->employee = Employee::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->stockService = new ComplaintStockService();
        $this->service = new ComplaintService(
            $this->stockService,
            new ComplaintItemService(),
            new ComplaintStatusTransitionService()
        );
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function makeWarehouse(string $code, string $name, int $quantity): Warehouse
    {
        return Warehouse::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'code' => $code,
            'name' => $name,
            'quantity' => $quantity,
        ]);
    }

    private function baseData(): array
    {
        return [
            'bus_id' => $this->bus->id,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'complaint_type' => 'nasazliq',
            'km' => 1000,
        ];
    }

    private function detailPayload(string $code, int $qty, ?string $notes = null): array
    {
        return [
            'shikayet_index' => 0,
            'code' => $code,
            'used_quantity' => $qty,
            'employee_id' => $this->employee->id,
            'notes' => $notes ?? "Test {$code}",
        ];
    }

    // ==================================================================
    // 1. UPDATE-IN-PLACE BEHAVIOR
    // ==================================================================

    public function test_detail_ids_are_preserved_when_quantity_changes(): void
    {
        $this->makeWarehouse('D-001', 'Filter', 100);
        $this->makeWarehouse('D-002', 'Bolt', 100);
        $this->makeWarehouse('D-003', 'Nut', 100);

        // İlk complaint — 3 detal
        $complaint = $this->service->create($this->baseData(), [
            $this->detailPayload('D-001', 2),
            $this->detailPayload('D-002', 3),
            $this->detailPayload('D-003', 4),
        ], ['Problem A']);

        $originalIds = $complaint->details()->orderBy('id')->pluck('id')->all();

        // İndi D-003-ün miqdarını 4 → 10 edək
        $this->service->update($complaint, $this->baseData(), [
            $this->detailPayload('D-001', 2),
            $this->detailPayload('D-002', 3),
            $this->detailPayload('D-003', 10),  // dəyişdi
        ], ['Problem A']);

        $newIds = $complaint->fresh()->details()->orderBy('id')->pluck('id')->all();

        // ✅ ID-lər qorunmalıdır (əvvəlki kod bunu pozurdu)
        $this->assertEquals($originalIds, $newIds, 'Detail IDs must be preserved on update');

        // ✅ Yalnız 3 aktiv detal olmalıdır
        $this->assertEquals(3, $complaint->fresh()->details()->count());

        // ✅ Soft-deleted heç bir sətir olmamalıdır
        $this->assertEquals(
            0,
            ComplaintDetail::onlyTrashed()->where('complaint_id', $complaint->id)->count(),
            'No soft-deleted details should accumulate on in-place updates'
        );

        // ✅ D-003-ün miqdarı dəyişmiş olmalıdır
        $d3 = $complaint->fresh()->details()->where('code', 'D-003')->first();
        $this->assertEquals(10, $d3->used_quantity);
    }

    public function test_no_soft_deleted_rows_accumulate_across_many_updates(): void
    {
        $this->makeWarehouse('D-010', 'A', 100);
        $this->makeWarehouse('D-011', 'B', 100);

        $complaint = $this->service->create($this->baseData(), [
            $this->detailPayload('D-010', 1),
            $this->detailPayload('D-011', 1),
        ], ['Problem']);

        // 10 dəfə update et — hər dəfə fərqli miqdar
        for ($i = 2; $i <= 11; $i++) {
            $this->service->update($complaint, $this->baseData(), [
                $this->detailPayload('D-010', $i),
                $this->detailPayload('D-011', $i),
            ], ['Problem']);
        }

        // ✅ 10 update-dən sonra heç bir soft-deleted sətir yığılmamalıdır
        $this->assertEquals(
            0,
            ComplaintDetail::onlyTrashed()->where('complaint_id', $complaint->id)->count(),
            'Repeated updates must not accumulate soft-deleted rows'
        );

        // ✅ Yalnız 2 aktiv detal
        $this->assertEquals(2, $complaint->fresh()->details()->count());
    }

    // ==================================================================
    // 2. ƏLAVƏ ETMƏ
    // ==================================================================

    public function test_adding_detail_creates_new_row_and_keeps_existing_ids(): void
    {
        $this->makeWarehouse('D-020', 'X', 50);
        $this->makeWarehouse('D-021', 'Y', 50);
        $this->makeWarehouse('D-022', 'Z', 50);

        $complaint = $this->service->create($this->baseData(), [
            $this->detailPayload('D-020', 1),
            $this->detailPayload('D-021', 1),
        ], ['Problem']);

        $originalIds = $complaint->details()->orderBy('id')->pluck('id')->all();

        // İndi 3-cü detalı əlavə et
        $this->service->update($complaint, $this->baseData(), [
            $this->detailPayload('D-020', 1),
            $this->detailPayload('D-021', 1),
            $this->detailPayload('D-022', 1),  // YENİ
        ], ['Problem']);

        $freshDetails = $complaint->fresh()->details()->orderBy('id')->get();

        // ✅ 3 detal olmalıdır
        $this->assertEquals(3, $freshDetails->count());

        // ✅ Köhnə 2 ID dəyişməyib
        $this->assertEquals($originalIds[0], $freshDetails[0]->id);
        $this->assertEquals($originalIds[1], $freshDetails[1]->id);

        // ✅ Yeni detal əlavə olunub
        $this->assertEquals('D-022', $freshDetails[2]->code);
    }

    // ==================================================================
    // 3. SİLMƏ
    // ==================================================================

    public function test_removing_detail_soft_deletes_only_that_row(): void
    {
        $this->makeWarehouse('D-030', 'A', 100);
        $this->makeWarehouse('D-031', 'B', 100);
        $this->makeWarehouse('D-032', 'C', 100);

        $complaint = $this->service->create($this->baseData(), [
            $this->detailPayload('D-030', 1),
            $this->detailPayload('D-031', 1),
            $this->detailPayload('D-032', 1),
        ], ['Problem']);

        $originalIds = $complaint->details()->orderBy('id')->pluck('id')->all();

        // Ortadakı D-031-i silək
        $this->service->update($complaint, $this->baseData(), [
            $this->detailPayload('D-030', 1),
            $this->detailPayload('D-032', 1),
        ], ['Problem']);

        $freshDetails = $complaint->fresh()->details()->orderBy('id')->get();

        // ✅ 2 aktiv detal
        $this->assertEquals(2, $freshDetails->count());
        $this->assertEquals('D-030', $freshDetails[0]->code);
        $this->assertEquals('D-032', $freshDetails[1]->code);

        // ✅ Birinci və üçüncü ID-lər qorunub (D-030 və D-032)
        $this->assertEquals($originalIds[0], $freshDetails[0]->id);
        $this->assertEquals($originalIds[2], $freshDetails[1]->id);

        // ✅ Yalnız 1 soft-deleted sətir (D-031)
        $this->assertEquals(
            1,
            ComplaintDetail::onlyTrashed()->where('complaint_id', $complaint->id)->count()
        );
    }

    public function test_removing_all_details_soft_deletes_all_and_restores_stock(): void
    {
        $w1 = $this->makeWarehouse('D-040', 'A', 100);
        $w2 = $this->makeWarehouse('D-041', 'B', 100);

        $complaint = $this->service->create($this->baseData(), [
            $this->detailPayload('D-040', 5),
            $this->detailPayload('D-041', 7),
        ], ['Problem']);

        // İlkin stok: 100 - 5 = 95, 100 - 7 = 93
        $this->assertEquals(95, $w1->fresh()->quantity);
        $this->assertEquals(93, $w2->fresh()->quantity);

        // İndi bütün detalları sil
        $this->service->update($complaint, $this->baseData(), [], ['Problem']);

        // ✅ Stok geri qaytarılmalıdır
        $this->assertEquals(100, $w1->fresh()->quantity);
        $this->assertEquals(100, $w2->fresh()->quantity);

        // ✅ Bütün detallar soft-deleted
        $this->assertEquals(0, $complaint->fresh()->details()->count());
        $this->assertEquals(
            2,
            ComplaintDetail::onlyTrashed()->where('complaint_id', $complaint->id)->count()
        );
    }

    // ==================================================================
    // 4. AUDIT LOG NOISE AZALMASI
    // ==================================================================

    public function test_update_with_single_change_produces_minimal_audit_logs(): void
    {
        $this->makeWarehouse('D-050', 'A', 100);
        $this->makeWarehouse('D-051', 'B', 100);
        $this->makeWarehouse('D-052', 'C', 100);

        $complaint = $this->service->create($this->baseData(), [
            $this->detailPayload('D-050', 1),
            $this->detailPayload('D-051', 1),
            $this->detailPayload('D-052', 1),
        ], ['Problem']);

        // Detallar üçün audit logları say
        $initialLogs = \App\Models\AuditLog::where('auditable_type', ComplaintDetail::class)
            ->whereIn('auditable_id', $complaint->details()->pluck('id'))
            ->count();

        // Yalnız bir detalın miqdarını dəyişdir
        $this->service->update($complaint, $this->baseData(), [
            $this->detailPayload('D-050', 1),
            $this->detailPayload('D-051', 5),  // yalnız bu dəyişdi
            $this->detailPayload('D-052', 1),
        ], ['Problem']);

        // Yeni audit loglarını say
        $newLogs = \App\Models\AuditLog::where('auditable_type', ComplaintDetail::class)
            ->whereIn('auditable_id', $complaint->details()->pluck('id'))
            ->count();

        $diff = $newLogs - $initialLogs;

        // ✅ Yalnız 1 audit log (1 update) olmalıdır
        // Köhnə kod: 3 deleted + 3 created = 6 log yaradardı
        $this->assertEquals(
            1,
            $diff,
            'Single detail change must produce exactly 1 audit log, not 6'
        );
    }

    // ==================================================================
    // 5. STOCK DIFF HƏLƏ İŞLƏYİR
    // ==================================================================

    public function test_stock_diff_still_works_with_in_place_sync(): void
    {
        $w1 = $this->makeWarehouse('D-060', 'A', 100);
        $w2 = $this->makeWarehouse('D-061', 'B', 100);

        $complaint = $this->service->create($this->baseData(), [
            $this->detailPayload('D-060', 5),
            $this->detailPayload('D-061', 3),
        ], ['Problem']);

        $this->assertEquals(95, $w1->fresh()->quantity);
        $this->assertEquals(97, $w2->fresh()->quantity);

        // A: 5 → 8 (daha çox istifadə), B: 3 → 1 (daha az istifadə)
        $this->service->update($complaint, $this->baseData(), [
            $this->detailPayload('D-060', 8),
            $this->detailPayload('D-061', 1),
        ], ['Problem']);

        // A: 100 - 8 = 92, B: 100 - 1 = 99
        $this->assertEquals(92, $w1->fresh()->quantity);
        $this->assertEquals(99, $w2->fresh()->quantity);
    }

    // ==================================================================
    // 6. UPDATE → CREATE → UPDATE → DELETE AXINI
    // ==================================================================

    public function test_full_lifecycle_does_not_leak_soft_deleted_rows(): void
    {
        $this->makeWarehouse('L-001', 'A', 100);
        $this->makeWarehouse('L-002', 'B', 100);
        $this->makeWarehouse('L-003', 'C', 100);

        // Create: 2 detal
        $complaint = $this->service->create($this->baseData(), [
            $this->detailPayload('L-001', 1),
            $this->detailPayload('L-002', 1),
        ], ['Problem']);

        // Update 1: 3 detal (bir əlavə)
        $this->service->update($complaint, $this->baseData(), [
            $this->detailPayload('L-001', 1),
            $this->detailPayload('L-002', 1),
            $this->detailPayload('L-003', 1),
        ], ['Problem']);

        // Update 2: 1 detal (ikisi silinir)
        $this->service->update($complaint, $this->baseData(), [
            $this->detailPayload('L-001', 2),
        ], ['Problem']);

        // Update 3: 2 detal (biri yenidən əlavə)
        $this->service->update($complaint, $this->baseData(), [
            $this->detailPayload('L-001', 2),
            $this->detailPayload('L-002', 1),
        ], ['Problem']);

        // Yalnız 2 aktiv detal
        $this->assertEquals(2, $complaint->fresh()->details()->count());

        // Soft-deleted sətir sayı: 4 (2 update + 2 delete)
        // Əslində: Update 1 → 0 silinmə, Update 2 → 2 silinmə,
        // Update 3 → 0 silinmə. Cəmi 2 soft-deleted.
        $this->assertEquals(
            2,
            ComplaintDetail::onlyTrashed()->where('complaint_id', $complaint->id)->count(),
            'Soft-deleted count should reflect only actual removals, not full rewrites'
        );
    }
}
