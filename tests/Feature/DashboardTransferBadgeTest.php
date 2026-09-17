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
use Tests\TestCase;

class DashboardTransferBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garageA;
    protected Garage $garageB;
    protected User $adminA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->adminA = User::factory()->create(['role' => 'user']);
        $this->adminA->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($this->garageA->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function sessionFor(Garage $garage): array
    {
        return [
            'current_garage_id'  => $garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    protected function makeItem(Garage $garage, string $code = 'FILTER-001', int $qty = 100): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $garage->id,
            'company_id' => $this->company->id,
            'code'       => $code,
            'name'       => "Part {$code}",
            'quantity'   => $qty,
        ]);
    }

    public function test_dashboard_shows_zero_when_no_transfers(): void
    {
        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('outboundPending', 0);
        $response->assertViewHas('inboundPending', 0);
        $response->assertViewHas('disputedCount', 0);

        // NOTE: We cannot assertDontSee('Warehouse Transfers') because
        // the sidebar navigation always contains a link with that
        // exact label. Instead we check for the panel's eyebrow text,
        // which is only rendered when at least one counter is > 0.
        $response->assertDontSee(__('messages.transfers.dashboard_eyebrow'), false);
    }

    public function test_dashboard_shows_inbound_pending_count(): void
    {
        $item = $this->makeItem($this->garageB);

        $service = app(WarehouseTransferService::class);
        $transfer = $service->create([
            'from_garage_id' => $this->garageB->id,
            'to_garage_id'   => $this->garageA->id,
            'type'           => TransferType::GarageToGarage->value,
            'items'          => [['warehouse_id' => $item->id, 'declared_quantity' => 5]],
        ], $this->company->id);

        // Dispatch as garage B (the source)
        $adminB = User::factory()->create(['role' => 'user']);
        $adminB->garages()->attach($this->garageB->id, ['role' => 'admin', 'is_active' => true]);
        $this->actingAs($adminB);
        $service->dispatch($transfer);

        // Now switch to garage A and check dashboard
        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('inboundPending', 1);
        $response->assertViewHas('outboundPending', 0);
        $response->assertSee(__('messages.transfers.dashboard_title'), false);
    }

    public function test_dashboard_shows_outbound_pending_count(): void
    {
        $item = $this->makeItem($this->garageA);

        $service = app(WarehouseTransferService::class);
        $transfer = $service->create([
            'from_garage_id' => $this->garageA->id,
            'to_garage_id'   => $this->garageB->id,
            'type'           => TransferType::GarageToGarage->value,
            'items'          => [['warehouse_id' => $item->id, 'declared_quantity' => 5]],
        ], $this->company->id);

        $this->actingAs($this->adminA);
        $service->dispatch($transfer);

        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('outboundPending', 1);
        $response->assertViewHas('inboundPending', 0);
    }

    public function test_dashboard_shows_disputed_count(): void
    {
        $item = $this->makeItem($this->garageA, 'FILTER-001', 50);

        $service = app(WarehouseTransferService::class);
        $transfer = $service->create([
            'from_garage_id' => $this->garageA->id,
            'to_garage_id'   => $this->garageB->id,
            'type'           => TransferType::GarageToGarage->value,
            'items'          => [['warehouse_id' => $item->id, 'declared_quantity' => 10]],
        ], $this->company->id);

        $this->actingAs($this->adminA);
        $service->dispatch($transfer);

        // Receive fewer at destination
        $adminB = User::factory()->create(['role' => 'user']);
        $adminB->garages()->attach($this->garageB->id, ['role' => 'admin', 'is_active' => true]);
        $this->actingAs($adminB);
        $service->receive($transfer->fresh(), [$transfer->items->first()->id => 6]);

        // Check dashboard from garage A
        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('disputedCount', 1);
    }

    public function test_dashboard_panel_hidden_when_all_counts_zero(): void
    {
        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee(__('messages.transfers.dashboard_eyebrow'), false);
    }

    public function test_unrelated_garage_transfers_not_counted(): void
    {
        // Create a garage C that has nothing to do with garage A
        $garageC = Garage::factory()->create(['company_id' => $this->company->id]);
        $item = $this->makeItem($garageC);

        $service = app(WarehouseTransferService::class);
        $transfer = $service->create([
            'from_garage_id' => $garageC->id,
            'to_garage_id'   => $this->garageB->id,
            'type'           => TransferType::GarageToGarage->value,
            'items'          => [['warehouse_id' => $item->id, 'declared_quantity' => 5]],
        ], $this->company->id);

        $adminC = User::factory()->create(['role' => 'user']);
        $adminC->garages()->attach($garageC->id, ['role' => 'admin', 'is_active' => true]);
        $this->actingAs($adminC);
        $service->dispatch($transfer);

        // Garage A admin should see ZERO pending
        $response = $this->actingAs($this->adminA)
            ->withSession($this->sessionFor($this->garageA))
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('outboundPending', 0);
        $response->assertViewHas('inboundPending', 0);
    }
}
