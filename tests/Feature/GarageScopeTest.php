<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Garage;
use App\Models\Company;
use App\Models\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_scope_filters_buses_by_current_garage()
    {
        $company = Company::factory()->create();

        $garage1 = Garage::factory()->create(['company_id' => $company->id]);
        $garage2 = Garage::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create(['role' => 'bus']);
        $user->garages()->attach($garage1, ['role' => 'bus', 'is_active' => true]);

        // Hər qaraja 2 avtobus əlavə et
        Bus::factory()->count(2)->create(['garage_id' => $garage1->id, 'company_id' => $company->id]);
        Bus::factory()->count(3)->create(['garage_id' => $garage2->id, 'company_id' => $company->id]);

        session(['current_garage_id' => $garage1->id]);

        $this->actingAs($user)->get(route('buses.index'));

        // Cari qarajda 2 avtobus olmalıdır
        $this->assertEquals(2, Bus::where('garage_id', $garage1->id)->count());
    }

    public function test_creating_bus_auto_assigns_garage_id()
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create(['role' => 'admin']);
        $user->garages()->attach($garage, ['role' => 'admin', 'is_active' => true]);

        session([
            'current_garage_id' => $garage->id,
            'current_company_id' => $company->id,
        ]);

        $this->actingAs($user)->post(route('buses.store'), [
            'dqn' => '99-AA-999',
            'route_number' => '123',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('buses', [
            'dqn' => '99-AA-999',
            'garage_id' => $garage->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_garage_filter_applies_to_complaints()
    {
        $company = Company::factory()->create();

        $garage1 = Garage::factory()->create(['company_id' => $company->id]);
        $garage2 = Garage::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create(['role' => 'complaint']);
        $user->garages()->attach($garage1, ['role' => 'complaint', 'is_active' => true]);

        $bus1 = Bus::factory()->create(['garage_id' => $garage1->id, 'company_id' => $company->id]);
        $bus2 = Bus::factory()->create(['garage_id' => $garage2->id, 'company_id' => $company->id]);

        // Hər qaraja 1 şikayət
        \App\Models\Complaint::factory()->create([
            'bus_id' => $bus1->id,
            'garage_id' => $garage1->id,
            'company_id' => $company->id,
            'status' => 'gözləmədə',
        ]);
        \App\Models\Complaint::factory()->create([
            'bus_id' => $bus2->id,
            'garage_id' => $garage2->id,
            'company_id' => $company->id,
            'status' => 'gözləmədə',
        ]);

        session(['current_garage_id' => $garage1->id]);

        $response = $this->actingAs($user)->get(route('complaints.index'));

        // Cari qarajda 1 şikayət olmalıdır
        $complaints = \App\Models\Complaint::all();
        $this->assertEquals(1, $complaints->count());
        $this->assertEquals($garage1->id, $complaints->first()->garage_id);
    }
}
