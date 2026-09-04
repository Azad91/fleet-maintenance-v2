<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Garage;
use App\Models\Company;
use App\Models\Bus;
use App\Models\Warehouse;
use App\Models\Employee;
use App\Models\Complaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintStockTest extends TestCase
{
    use RefreshDatabase;

    protected $garage;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();
        $this->garage = Garage::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->user = User::factory()->create(['role' => 'complaint']);
        $this->user->garages()->attach($this->garage, ['role' => 'complaint', 'is_active' => true]);
    }

    public function test_stock_decreases_when_complaint_created()
    {
        $bus = Bus::factory()->create(['garage_id' => $this->garage->id]);

        $warehouse = Warehouse::factory()->create([
            'garage_id' => $this->garage->id,
            'code' => 'D-001',
            'name' => 'Test Detail',
            'quantity' => 10,
        ]);

        $employee = Employee::factory()->create([
            'garage_id' => $this->garage->id,
        ]);

        session(['current_garage_id' => $this->garage->id]);

        $response = $this->actingAs($this->user)->post(route('complaints.store'), [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'shikayet' => ['Test complaint'],
            'complaint_type' => 'nasazliq',
            'status' => 'gözləmədə',
            'detallar' => [
                [
                    'kodu' => 'D-001',
                    'islenen_miqdar' => 3,
                    'employee_id' => $employee->id,
                    'qeyd' => 'Test note',
                ]
            ],
            'start_date' => now()->format('Y-m-d'),
            'start_time' => now()->format('H:i'),
            'end_date' => now()->format('Y-m-d'),
            'end_time' => now()->format('H:i'),
        ]);

        // Stock azalıb?
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'quantity' => 7, // 10 - 3 = 7
        ]);

        // Complaint_details-də qeyd var?
        $this->assertDatabaseHas('complaint_details', [
            'code' => 'D-001',
            'used_quantity' => 3,
        ]);
    }

    public function test_stock_restored_when_complaint_deleted()
    {
        $bus = Bus::factory()->create(['garage_id' => $this->garage->id]);

        $warehouse = Warehouse::factory()->create([
            'garage_id' => $this->garage->id,
            'code' => 'D-001',
            'quantity' => 10,
        ]);

        $employee = Employee::factory()->create([
            'garage_id' => $this->garage->id,
        ]);

        session(['current_garage_id' => $this->garage->id]);

        // 1. Şikayət yarat
        $response = $this->actingAs($this->user)->post(route('complaints.store'), [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'shikayet' => ['Test complaint'],
            'complaint_type' => 'nasazliq',
            'status' => 'gözləmədə',
            'detallar' => [
                [
                    'kodu' => 'D-001',
                    'islenen_miqdar' => 3,
                    'employee_id' => $employee->id,
                    'qeyd' => 'Test note',
                ]
            ],
            'start_date' => now()->format('Y-m-d'),
            'start_time' => now()->format('H:i'),
            'end_date' => now()->format('Y-m-d'),
            'end_time' => now()->format('H:i'),
        ]);

        $complaint = Complaint::first();

        // 2. Anbar yeniləndi (10 -> 7)
        $this->assertEquals(7, $warehouse->fresh()->quantity);

        // 3. Şikayəti sil (Admin olmalıyıq)
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->garages()->attach($this->garage, ['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->delete(route('complaints.destroy', $complaint));

        // 4. Anbar geri qayıtdı (7 -> 10)
        $this->assertEquals(10, $warehouse->fresh()->quantity);
    }

    public function test_cannot_create_complaint_with_insufficient_stock()
    {
        $bus = Bus::factory()->create(['garage_id' => $this->garage->id]);

        $warehouse = Warehouse::factory()->create([
            'garage_id' => $this->garage->id,
            'code' => 'D-001',
            'quantity' => 2, // Cəmi 2 ədəd var
        ]);

        $employee = Employee::factory()->create([
            'garage_id' => $this->garage->id,
        ]);

        session(['current_garage_id' => $this->garage->id]);

        $response = $this->actingAs($this->user)->post(route('complaints.store'), [
            'bus_id' => $bus->id,
            'yer' => 'qaraj',
            'shikayet' => ['Test complaint'],
            'complaint_type' => 'nasazliq',
            'status' => 'gözləmədə',
            'detallar' => [
                [
                    'kodu' => 'D-001',
                    'islenen_miqdar' => 5, // 5 tələb edir, amma 2 var
                    'employee_id' => $employee->id,
                    'qeyd' => 'Test note',
                ]
            ],
            'start_date' => now()->format('Y-m-d'),
            'start_time' => now()->format('H:i'),
            'end_date' => now()->format('Y-m-d'),
            'end_time' => now()->format('H:i'),
        ]);

        $response->assertSessionHasErrors(['detallar']);
        $this->assertEquals(2, $warehouse->fresh()->quantity); // Dəyişməyib
    }
}
