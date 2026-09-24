<?php

namespace Tests\Feature;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Company;
use App\Models\Garage;
use App\Models\ServiceVehicle;
use App\Models\ServiceVehicleStock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use App\Services\Warehouse\WarehouseTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiceVehicleStockTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected User $admin;

    protected ServiceVehicle $vehicle;

    protected Warehouse $warehouse;

    protected WarehouseTransferService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garage->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->vehicle = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'name' => 'Service Vehicle 1',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'code' => 'FILTER-001',
            'name' => 'Oil Filter',
            'unit' => 'piece',
            'quantity' => 50,
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

    // ==================================================================
    // 1. HAPPY PATH
    // ==================================================================

    public function test_transfer_creates_new_service_vehicle_stock_row(): void
    {
        $this->actingAs($this->admin);

        $transfer = $this->service->createAndComplete([
            'from_garage_id' => $this->garage->id,
            'to_service_vehicle_id' => $this->vehicle->id,
            'type' => TransferType::ToServiceVehicle->value,
            'items' => [
                ['warehouse_id' => $this->warehouse->id, 'declared_quantity' => 10],
            ],
        ], $this->company->id);

        $this->assertSame(TransferStatus::Received, $transfer->status);

        // Source stock decreased
        $this->assertSame(40, $this->warehouse->fresh()->quantity);

        // Service vehicle stock created
        $stock = ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicle->id)
            ->where('code', 'FILTER-001')
            ->first();

        $this->assertNotNull($stock);
        $this->assertSame(10, $stock->quantity);
        $this->assertSame('Oil Filter', $stock->name);
        $this->assertSame('piece', $stock->unit);
    }

    public function test_second_transfer_accumulates_into_same_row(): void
    {
        $this->actingAs($this->admin);

        foreach ([5, 3, 2] as $qty) {
            $this->service->createAndComplete([
                'from_garage_id' => $this->garage->id,
                'to_service_vehicle_id' => $this->vehicle->id,
                'type' => TransferType::ToServiceVehicle->value,
                'items' => [
                    ['warehouse_id' => $this->warehouse->id, 'declared_quantity' => $qty],
                ],
            ], $this->company->id);
        }

        $stock = ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicle->id)
            ->where('code', 'FILTER-001')
            ->first();

        $this->assertSame(10, $stock->quantity);
        $this->assertSame(40, $this->warehouse->fresh()->quantity);

        // Only one stock row exists
        $this->assertSame(1, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicle->id)
            ->count());
    }

    public function test_transfer_to_different_vehicles_creates_separate_rows(): void
    {
        $this->actingAs($this->admin);

        $vehicle2 = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'name' => 'Service Vehicle 2',
            'is_active' => true,
        ]);

        $this->service->createAndComplete([
            'from_garage_id' => $this->garage->id,
            'to_service_vehicle_id' => $this->vehicle->id,
            'type' => TransferType::ToServiceVehicle->value,
            'items' => [['warehouse_id' => $this->warehouse->id, 'declared_quantity' => 5]],
        ], $this->company->id);

        $this->service->createAndComplete([
            'from_garage_id' => $this->garage->id,
            'to_service_vehicle_id' => $vehicle2->id,
            'type' => TransferType::ToServiceVehicle->value,
            'items' => [['warehouse_id' => $this->warehouse->id, 'declared_quantity' => 7]],
        ], $this->company->id);

        $this->assertSame(5, ServiceVehicleStock::withoutGlobalScopes()->where('service_vehicle_id', $this->vehicle->id)->sum('quantity'));
        $this->assertSame(7, ServiceVehicleStock::withoutGlobalScopes()->where('service_vehicle_id', $vehicle2->id)->sum('quantity'));
        $this->assertSame(38, $this->warehouse->fresh()->quantity);
    }

    // ==================================================================
    // 2. GUARDS
    // ==================================================================

    public function test_transfer_rejected_when_stock_insufficient(): void
    {
        $this->actingAs($this->admin);

        $this->expectException(ValidationException::class);

        $this->service->createAndComplete([
            'from_garage_id' => $this->garage->id,
            'to_service_vehicle_id' => $this->vehicle->id,
            'type' => TransferType::ToServiceVehicle->value,
            'items' => [
                ['warehouse_id' => $this->warehouse->id, 'declared_quantity' => 100],
            ],
        ], $this->company->id);
    }

    public function test_transfer_cannot_use_garage_to_garage_workflow(): void
    {
        $this->actingAs($this->admin);

        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->expectException(ValidationException::class);

        // createAndComplete is only for immediate transfers
        $this->service->createAndComplete([
            'from_garage_id' => $this->garage->id,
            'to_garage_id' => $otherGarage->id,
            'type' => TransferType::GarageToGarage->value,
            'items' => [['warehouse_id' => $this->warehouse->id, 'declared_quantity' => 5]],
        ], $this->company->id);
    }

    // ==================================================================
    // 3. HTTP LAYER
    // ==================================================================

    public function test_http_transfer_to_service_vehicle_completes_immediately(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->sessionFor($this->garage))
            ->post(route('warehouse-transfers.store'), [
                'type' => TransferType::ToServiceVehicle->value,
                'to_service_vehicle_id' => $this->vehicle->id,
                'items' => [
                    ['warehouse_id' => $this->warehouse->id, 'declared_quantity' => 3],
                ],
            ]);

        $transfer = \App\Models\WarehouseTransfer::latest('id')->first();

        $response->assertRedirect(route('warehouse-transfers.show', $transfer));
        $this->assertSame(TransferStatus::Received, $transfer->status);
        $this->assertSame(3, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicle->id)
            ->sum('quantity'));
    }

    public function test_service_vehicle_show_page_displays_stock(): void
    {
        $this->actingAs($this->admin);

        ServiceVehicleStock::withoutGlobalScopes()->create([
            'service_vehicle_id' => $this->vehicle->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'code' => 'FILTER-001',
            'name' => 'Oil Filter',
            'unit' => 'piece',
            'quantity' => 15,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->sessionFor($this->garage))
            ->get(route('service-vehicles.show', $this->vehicle));

        $response->assertOk();
        $response->assertSee(__('messages.service_vehicles.current_stock'), false);
        $response->assertSee('FILTER-001');
        $response->assertSee('15');
    }
}
