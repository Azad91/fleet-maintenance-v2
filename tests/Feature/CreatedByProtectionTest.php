<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use App\Models\Company;
use App\Models\DailyKmRecord;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatedByProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected User $admin;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create([
            'role' => 'user',
            'name' => 'Real Admin',
        ]);
        $this->admin->garages()->attach($this->garage->id, [
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->otherUser = User::factory()->create([
            'role' => 'user',
            'name' => 'Spoofed User',
        ]);

        GarageContext::set($this->garage->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function garageSession(): array
    {
        return [
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    // ==================================================================
    // 1. FILLABLE SƏHVƏLƏRİ
    // ==================================================================

    public function test_created_by_is_not_fillable_on_warehouse(): void
    {
        $this->assertNotContains('created_by', (new Warehouse())->getFillable());
    }

    public function test_created_by_is_not_fillable_on_daily_km_record(): void
    {
        $this->assertNotContains('created_by', (new DailyKmRecord())->getFillable());
    }

    public function test_created_by_is_not_fillable_on_bus_daily_status(): void
    {
        $this->assertNotContains('created_by', (new BusDailyStatus())->getFillable());
    }

    // ==================================================================
    // 2. HTTP LEVEL — SPOOFING CƏHDİ
    // ==================================================================

    public function test_warehouse_created_by_reflects_authenticated_user_not_spoofed_value(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('warehouses.store'), [
                'code'       => 'W-SPOOF-TEST',
                'name'       => 'Spoof Test',
                'quantity'   => 10,
                'created_by' => $this->otherUser->id, // ← spoofing attempt
            ]);

        $warehouse = Warehouse::withoutGlobalScopes()
            ->where('code', 'W-SPOOF-TEST')
            ->first();

        $this->assertNotNull($warehouse, 'Warehouse must be created');
        $this->assertEquals(
            $this->admin->id,
            $warehouse->created_by,
            'created_by must reflect the authenticated user'
        );
        $this->assertNotEquals(
            $this->otherUser->id,
            $warehouse->created_by,
            'Spoofed created_by must be ignored'
        );
    }

    public function test_daily_km_record_created_by_reflects_authenticated_user(): void
    {
        $bus = Bus::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('daily-km-records.store'), [
                'bus_id'     => $bus->id,
                'date'       => now()->toDateString(),
                'km'         => 15000,
                'created_by' => $this->otherUser->id,
            ]);

        $record = DailyKmRecord::withoutGlobalScopes()
            ->where('bus_id', $bus->id)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals($this->admin->id, $record->created_by);
        $this->assertNotEquals($this->otherUser->id, $record->created_by);
    }

    public function test_bus_daily_status_created_by_reflects_authenticated_user(): void
    {
        $bus = Bus::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('bus-daily-statuses.store'), [
                'bus_id'     => $bus->id,
                'date'       => now()->toDateString(),
                'status'     => 'READY',
                'created_by' => $this->otherUser->id,
            ]);

        $status = BusDailyStatus::withoutGlobalScopes()
            ->where('bus_id', $bus->id)
            ->first();

        $this->assertNotNull($status);
        $this->assertEquals($this->admin->id, $status->created_by);
        $this->assertNotEquals($this->otherUser->id, $status->created_by);
    }

    // ==================================================================
    // 3. MODEL LEVEL — BİRBAŞA CREATE
    // ==================================================================

    public function test_direct_model_create_ignores_created_by_without_auth(): void
    {
        $warehouse = Warehouse::create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'code'       => 'W-NOAUTH',
            'name'       => 'No Auth Create',
            'quantity'   => 5,
            'created_by' => $this->otherUser->id, // must be ignored
        ]);

        $this->assertNull(
            $warehouse->created_by,
            'created_by must be null when no user is authenticated'
        );
    }

    public function test_direct_model_create_uses_authenticated_user_for_created_by(): void
    {
        $this->actingAs($this->admin);

        $warehouse = Warehouse::create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'code'       => 'W-AUTHDIRECT',
            'name'       => 'Auth Direct Create',
            'quantity'   => 5,
            'created_by' => $this->otherUser->id, // must be ignored
        ]);

        $this->assertEquals(
            $this->admin->id,
            $warehouse->created_by,
            'created_by must be auto-populated from authenticated user'
        );
    }

    // ==================================================================
    // 4. LEGİTİM MANUAL SET — FORCEFILL İLƏ
    // ==================================================================

    public function test_created_by_can_be_set_via_forcefill_when_needed(): void
    {
        $this->actingAs($this->admin);

        $warehouse = Warehouse::create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'code'       => 'W-FORCEFILL',
            'name'       => 'ForceFill Test',
            'quantity'   => 5,
        ]);

        // Bəzi hallarda sistem başqa user adına yaza bilər (məsələn import)
        $warehouse->forceFill(['created_by' => $this->otherUser->id])->save();

        $warehouse->refresh();
        $this->assertEquals($this->otherUser->id, $warehouse->created_by);
    }
}
