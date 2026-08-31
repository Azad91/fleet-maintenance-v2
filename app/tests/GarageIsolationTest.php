<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Warehouse;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 2 qaraj yarat
        Garage::factory()->create(['id' => 1, 'name' => 'Qaraj 1']);
        Garage::factory()->create(['id' => 2, 'name' => 'Qaraj 2']);
    }

    /** @test */
    public function it_only_shows_buses_from_current_garage()
    {
        // Qaraj 1-də 2 avtobus
        Bus::factory()->count(2)->create(['garage_id' => 1]);
        // Qaraj 2-də 3 avtobus
        Bus::factory()->count(3)->create(['garage_id' => 2]);

        // Qaraj 1 seç
        session(['current_garage_id' => 1]);

        // HasGarageScope filtr etməlidir
        $this->assertEquals(2, Bus::count());

        // Qaraj 2 seç
        session(['current_garage_id' => 2]);

        // HasGarageScope filtr etməlidir
        $this->assertEquals(3, Bus::count());
    }

    /** @test */
    public function it_only_shows_complaints_from_current_garage()
    {
        // Qaraj 1-də 2 şikayət
        Complaint::factory()->count(2)->create(['garage_id' => 1]);
        // Qaraj 2-də 4 şikayət
        Complaint::factory()->count(4)->create(['garage_id' => 2]);

        session(['current_garage_id' => 1]);
        $this->assertEquals(2, Complaint::count());

        session(['current_garage_id' => 2]);
        $this->assertEquals(4, Complaint::count());
    }

    /** @test */
    public function it_only_shows_warehouse_items_from_current_garage()
    {
        Warehouse::factory()->count(2)->create(['garage_id' => 1]);
        Warehouse::factory()->count(3)->create(['garage_id' => 2]);

        session(['current_garage_id' => 1]);
        $this->assertEquals(2, Warehouse::count());

        session(['current_garage_id' => 2]);
        $this->assertEquals(3, Warehouse::count());
    }

    /** @test */
    public function it_automatically_assigns_garage_id_when_creating()
    {
        session(['current_garage_id' => 1]);

        $bus = Bus::factory()->create();
        $this->assertEquals(1, $bus->garage_id);

        $complaint = Complaint::factory()->create();
        $this->assertEquals(1, $complaint->garage_id);

        $warehouse = Warehouse::factory()->create();
        $this->assertEquals(1, $warehouse->garage_id);
    }
}
