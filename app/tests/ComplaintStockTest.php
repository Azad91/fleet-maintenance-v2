<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Warehouse;
use App\Services\Complaint\ComplaintService;
use App\Services\Complaint\ComplaintStockService;
use App\Services\Complaint\ComplaintItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComplaintStockTest extends TestCase
{
    use RefreshDatabase;

    private ComplaintService $complaintService;
    private ComplaintStockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        // Session-a qaraj ID əlavə et (HasGarageScope üçün)
        session(['current_garage_id' => 1]);

        $this->stockService = new ComplaintStockService();
        $this->complaintService = new ComplaintService(
            $this->stockService,
            new ComplaintItemService()
        );
    }

    /** @test */
    public function it_deducts_stock_when_complaint_is_created()
    {
        // 1. Anbarda 10 ədəd detal var
        $warehouse = Warehouse::factory()->create([
            'kod' => 'D-001',
            'ad' => 'Test Detal',
            'miqdar' => 10,
            'garage_id' => 1,
        ]);

        // 2. Avtobus yarat
        $bus = Bus::factory()->create(['garage_id' => 1]);

        // 3. Kart aç (1 detal istifadə et)
        $data = [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'sikayet_tipi' => 'nasazliq',
            'km' => 1000,
        ];

        $detallar = [
            [
                'kodu' => 'D-001',
                'islenen_miqdar' => 3,
                'employee_id' => Employee::factory()->create(['garage_id' => 1])->id,
                'qeyd' => 'Test qeydi',
            ]
        ];

        $shikayet = ['Test şikayəti'];

        // 4. Əməliyyatı icra et
        $complaint = $this->complaintService->create($data, $detallar, $shikayet);

        // 5. Anbar yenilənməlidir (10 - 3 = 7)
        $warehouse->refresh();
        $this->assertEquals(7, $warehouse->miqdar);

        // 6. Kartda detallar saxlanılmalıdır
        $this->assertNotEmpty($complaint->detallar);
        $this->assertEquals('D-001', $complaint->detallar[0]['kodu']);
        $this->assertEquals(3, $complaint->detallar[0]['islenen_miqdar']);
    }

    /** @test */
    public function it_prevents_negative_stock()
    {
        // 1. Anbarda 2 ədəd detal var
        $warehouse = Warehouse::factory()->create([
            'kod' => 'D-002',
            'ad' => 'Test Detal 2',
            'miqdar' => 2,
            'garage_id' => 1,
        ]);

        // 2. Avtobus yarat
        $bus = Bus::factory()->create(['garage_id' => 1]);

        // 3. Kart aç (5 ədəd istifadə et — anbarda 2 var)
        $data = [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'sikayet_tipi' => 'nasazliq',
            'km' => 1000,
        ];

        $detallar = [
            [
                'kodu' => 'D-002',
                'islenen_miqdar' => 5, // anbarda 2 var!
                'employee_id' => Employee::factory()->create(['garage_id' => 1])->id,
                'qeyd' => 'Test qeydi',
            ]
        ];

        $shikayet = ['Test şikayəti'];

        // 4. Bu əməliyyat ValidationException atmalıdır
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Anbarda kifayət qədər');

        $this->complaintService->create($data, $detallar, $shikayet);

        // 5. Anbar dəyişməz qalmalıdır (2)
        $warehouse->refresh();
        $this->assertEquals(2, $warehouse->miqdar);
    }

    /** @test */
    public function it_restores_stock_when_complaint_is_deleted()
    {
        // 1. Anbarda 10 ədəd detal var
        $warehouse = Warehouse::factory()->create([
            'kod' => 'D-003',
            'ad' => 'Test Detal 3',
            'miqdar' => 10,
            'garage_id' => 1,
        ]);

        // 2. Avtobus yarat
        $bus = Bus::factory()->create(['garage_id' => 1]);

        // 3. Kart aç (3 detal istifadə et)
        $data = [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'sikayet_tipi' => 'nasazliq',
            'km' => 1000,
        ];

        $detallar = [
            [
                'kodu' => 'D-003',
                'islenen_miqdar' => 3,
                'employee_id' => Employee::factory()->create(['garage_id' => 1])->id,
                'qeyd' => 'Test qeydi',
            ]
        ];

        $shikayet = ['Test şikayəti'];

        $complaint = $this->complaintService->create($data, $detallar, $shikayet);

        // 4. Anbar 7 olmalıdır (10-3)
        $warehouse->refresh();
        $this->assertEquals(7, $warehouse->miqdar);

        // 5. Kartı sil (stok geri qayıtmalıdır)
        $this->complaintService->delete($complaint);

        // 6. Anbar 10 olmalıdır (7+3)
        $warehouse->refresh();
        $this->assertEquals(10, $warehouse->miqdar);
    }

    /** @test */
    public function it_restores_old_stock_and_deducts_new_on_update()
    {
        // 1. Anbarda 20 ədəd detal var
        $warehouse = Warehouse::factory()->create([
            'kod' => 'D-004',
            'ad' => 'Test Detal 4',
            'miqdar' => 20,
            'garage_id' => 1,
        ]);

        // 2. Avtobus yarat
        $bus = Bus::factory()->create(['garage_id' => 1]);

        // 3. Kart aç (5 detal istifadə et)
        $data = [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'sikayet_tipi' => 'nasazliq',
            'km' => 1000,
        ];

        $detallar = [
            [
                'kodu' => 'D-004',
                'islenen_miqdar' => 5,
                'employee_id' => Employee::factory()->create(['garage_id' => 1])->id,
                'qeyd' => 'Test qeydi',
            ]
        ];

        $shikayet = ['Test şikayəti'];

        $complaint = $this->complaintService->create($data, $detallar, $shikayet);

        // 4. Anbar 15 olmalıdır (20-5)
        $warehouse->refresh();
        $this->assertEquals(15, $warehouse->miqdar);

        // 5. Kartı yenilə (3 detal istifadə et — azaldı)
        $newDetallar = [
            [
                'kodu' => 'D-004',
                'islenen_miqdar' => 3,
                'employee_id' => Employee::factory()->create(['garage_id' => 1])->id,
                'qeyd' => 'Yeni qeyd',
            ]
        ];

        $this->complaintService->update($complaint, $data, $newDetallar, $shikayet);

        // 6. Anbar 17 olmalıdır (15-3=12? Düzgün hesablama: 20-5+5-3=17)
        // Əvvəl 5 geri qayıtdı → 20, sonra 3 çıxdı → 17
        $warehouse->refresh();
        $this->assertEquals(17, $warehouse->miqdar);
    }
}
