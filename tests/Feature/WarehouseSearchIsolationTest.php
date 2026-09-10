<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseSearchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->user = User::factory()->create(['role' => 'user']);
        $this->user->garages()->attach($this->garageA->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        GarageContext::set($this->garageA->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function garageSession(): array
    {
        return [
            'current_garage_id'  => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ];
    }

    public function test_search_by_code_does_not_leak_other_garages(): void
    {
        Warehouse::factory()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'code' => 'D-001',
            'name' => 'Filter Alpha',
        ]);

        Warehouse::factory()->create([
            'garage_id' => $this->garageB->id,
            'company_id' => $this->company->id,
            'code' => 'D-001',
            'name' => 'Filter Beta',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->get(route('warehouses.search', ['search' => 'D-001']));

        $content = $response->getContent();
        $this->assertStringContainsString('Filter Alpha', $content);
        $this->assertStringNotContainsString('Filter Beta', $content,
            'CRITICAL: other garage warehouse leaked via search');
    }

    public function test_search_by_name_does_not_leak_other_garages(): void
    {
        // ✅ Ən kritik test: name axtarışında orWhere bug-ını tutur
        Warehouse::factory()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'code' => 'A-001',
            'name' => 'Same Name',
        ]);

        Warehouse::factory()->create([
            'garage_id' => $this->garageB->id,
            'company_id' => $this->company->id,
            'code' => 'B-001',
            'name' => 'Same Name',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->get(route('warehouses.search', ['search' => 'Same Name']));

        $content = $response->getContent();
        $this->assertStringContainsString('A-001', $content);
        $this->assertStringNotContainsString('B-001', $content,
            'CRITICAL: cross-garage leak via name search (orWhere scope bug)');
    }

    public function test_index_search_does_not_leak_other_garages(): void
    {
        // Eyni test index() üçün (query string ilə)
        Warehouse::factory()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'code' => 'A-002',
            'name' => 'Common Name',
        ]);

        Warehouse::factory()->create([
            'garage_id' => $this->garageB->id,
            'company_id' => $this->company->id,
            'code' => 'B-002',
            'name' => 'Common Name',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->get(route('warehouses.index', ['search' => 'Common Name']));
            
        $content = $response->getContent();
        $this->assertStringContainsString('A-002', $content);
        $this->assertStringNotContainsString('B-002', $content,
            'CRITICAL: index() leaked cross-tenant warehouse');
    }

    public function test_search_returns_empty_when_no_match(): void
    {
        Warehouse::factory()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'code' => 'A-003',
            'name' => 'Test',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->get(route('warehouses.search', ['search' => 'XYZ-NOT-FOUND']));

        $response->assertOk();
        $response->assertDontSee('A-003');
    }
}
