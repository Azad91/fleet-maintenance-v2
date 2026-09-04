<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Employee;
use App\Models\Garage;
use App\Models\Warehouse;
use App\Services\Complaint\ComplaintService;
use App\Services\Complaint\ComplaintStockService;
use App\Services\Complaint\ComplaintItemService;
use App\Services\GarageContext;
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

        $company = Company::factory()->create(['id' => 1]);
        Garage::factory()->create(['id' => 1, 'company_id' => $company->id]);

        session(['current_garage_id' => 1]);
        session(['current_company_id' => 1]);
        GarageContext::set(1, 1);

        $this->stockService = new ComplaintStockService();
        $this->complaintService = new ComplaintService(
            $this->stockService,
            new ComplaintItemService()
        );
    }

    public function test_it_deducts_stock_when_complaint_is_created()
    {
        $warehouse = Warehouse::factory()->create([
            'code' => 'D-001',
            'name' => 'Test Detal',
            'quantity' => 10,
            'garage_id' => 1,
            'company_id' => 1,
        ]);

        $bus = Bus::factory()->create(['garage_id' => 1, 'company_id' => 1]);

        $data = [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'complaint_type' => 'nasazliq',
            'km' => 1000,
        ];

        $detallar = [
            [
                'code' => 'D-001',
                'used_quantity' => 3,
                'employee_id' => Employee::factory()->create(['garage_id' => 1, 'company_id' => 1])->id,
                'notes' => 'Test qeydi',
            ]
        ];

        $shikayet = ['Test şikayəti'];

        $complaint = $this->complaintService->create($data, $detallar, $shikayet);

        $warehouse->refresh();
        $this->assertEquals(7, $warehouse->quantity);

        $complaint->load('details');
        $this->assertNotEmpty($complaint->details);
        $this->assertEquals('D-001', $complaint->details[0]->code);
        $this->assertEquals(3, $complaint->details[0]->used_quantity);
    }

    public function test_it_prevents_negative_stock()
    {
        $warehouse = Warehouse::factory()->create([
            'code' => 'D-002',
            'name' => 'Test Detal 2',
            'quantity' => 2,
            'garage_id' => 1,
            'company_id' => 1,
        ]);

        $bus = Bus::factory()->create(['garage_id' => 1, 'company_id' => 1]);

        $data = [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'complaint_type' => 'nasazliq',
            'km' => 1000,
        ];

        $detallar = [
            [
                'code' => 'D-002',
                'used_quantity' => 5,
                'employee_id' => Employee::factory()->create(['garage_id' => 1, 'company_id' => 1])->id,
                'notes' => 'Test qeydi',
            ]
        ];

        $shikayet = ['Test şikayəti'];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Anbarda kifayət qədər');

        $this->complaintService->create($data, $detallar, $shikayet);

        $warehouse->refresh();
        $this->assertEquals(2, $warehouse->quantity);
    }

    public function test_it_restores_stock_when_complaint_is_deleted()
    {
        $warehouse = Warehouse::factory()->create([
            'code' => 'D-003',
            'name' => 'Test Detal 3',
            'quantity' => 10,
            'garage_id' => 1,
            'company_id' => 1,
        ]);

        $bus = Bus::factory()->create(['garage_id' => 1, 'company_id' => 1]);

        $data = [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'complaint_type' => 'nasazliq',
            'km' => 1000,
        ];

        $detallar = [
            [
                'code' => 'D-003',
                'used_quantity' => 3,
                'employee_id' => Employee::factory()->create(['garage_id' => 1, 'company_id' => 1])->id,
                'notes' => 'Test qeydi',
            ]
        ];

        $shikayet = ['Test şikayəti'];

        $complaint = $this->complaintService->create($data, $detallar, $shikayet);

        $warehouse->refresh();
        $this->assertEquals(7, $warehouse->quantity);

        $this->complaintService->delete($complaint);

        $warehouse->refresh();
        $this->assertEquals(10, $warehouse->quantity);
    }

    public function test_it_restores_old_stock_and_deducts_new_on_update()
    {
        $warehouse = Warehouse::factory()->create([
            'code' => 'D-004',
            'name' => 'Test Detal 4',
            'quantity' => 20,
            'garage_id' => 1,
            'company_id' => 1,
        ]);

        $bus = Bus::factory()->create(['garage_id' => 1, 'company_id' => 1]);

        $data = [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'complaint_type' => 'nasazliq',
            'km' => 1000,
        ];

        $detallar = [
            [
                'code' => 'D-004',
                'used_quantity' => 5,
                'employee_id' => Employee::factory()->create(['garage_id' => 1, 'company_id' => 1])->id,
                'notes' => 'Test qeydi',
            ]
        ];

        $shikayet = ['Test şikayəti'];

        $complaint = $this->complaintService->create($data, $detallar, $shikayet);

        $warehouse->refresh();
        $this->assertEquals(15, $warehouse->quantity);

        $newDetallar = [
            [
                'code' => 'D-004',
                'used_quantity' => 3,
                'employee_id' => Employee::factory()->create(['garage_id' => 1, 'company_id' => 1])->id,
                'notes' => 'Yeni qeyd',
            ]
        ];

        $this->complaintService->update($complaint, $data, $newDetallar, $shikayet);

        $warehouse->refresh();
        $this->assertEquals(17, $warehouse->quantity);
    }
}
