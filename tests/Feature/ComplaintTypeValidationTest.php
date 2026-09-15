<?php

namespace Tests\Feature;

use App\Enums\ComplaintType;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintType as ComplaintTypeModel;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintTypeValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected User $admin;

    /**
     * A name that exists in complaint_types for the current garage.
     * The `complaints.*` validation rule requires each description
     * to match an existing complaint type name.
     */
    protected string $validDescription = 'Engine noise';

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        // Seed a complaint type for the current garage, so the
        // `complaints.*` validation rule (Rule::exists) passes.
        ComplaintTypeModel::withoutGlobalScopes()->create([
            'name' => $this->validDescription,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function garageSession(): array
    {
        return [
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'bus_id' => $this->bus->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
            'complaints' => [$this->validDescription],
        ], $overrides);
    }

    // ==================================================================
    // 1. STORE — VALIDATION
    // ==================================================================

    public function test_store_accepts_accident_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), $this->basePayload([
                'complaint_type' => 'accident',
            ]));

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, Complaint::count());
    }

    public function test_store_accepts_breakdown_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), $this->basePayload([
                'complaint_type' => 'breakdown',
            ]));

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, Complaint::count());
    }

    public function test_store_accepts_maintenance_type_with_service_km(): void
    {
        // Maintenance complaints are tied to a motor-oil interval;
        // ComplaintStoreRequest requires service_km for this type
        // (Rule::requiredIf on complaint_type === maintenance).
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), $this->basePayload([
                'complaint_type' => 'maintenance',
                'service_km' => 36000,
            ]));

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, Complaint::count());
    }

    public function test_store_rejects_maintenance_type_without_service_km(): void
    {
        // Sanity guard: without service_km a maintenance complaint must
        // be rejected — otherwise the interval information is lost.
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), $this->basePayload([
                'complaint_type' => 'maintenance',
            ]));

        $response->assertSessionHasErrors('service_km');
        $this->assertEquals(0, Complaint::count());
    }

    public function test_store_rejects_invalid_complaint_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), $this->basePayload([
                'complaint_type' => 'fire_damage',
            ]));

        $response->assertSessionHasErrors('complaint_type');
        $this->assertEquals(0, Complaint::count());
    }

    public function test_store_accepts_null_complaint_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), $this->basePayload([
                'complaint_type' => null,
            ]));

        $response->assertSessionHasNoErrors();

        $complaint = Complaint::first();
        $this->assertNotNull($complaint);
        $this->assertNull($complaint->complaint_type);
    }

    // ==================================================================
    // 2. UPDATE — VALIDATION
    // ==================================================================

    public function test_update_accepts_valid_complaint_type(): void
    {
        $complaint = Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->put(route('complaints.update', $complaint), [
                'bus_id' => $this->bus->id,
                'yer' => 'garage',
                'status' => 'pending',
                'complaint_type' => 'accident',
                'complaints' => [$this->validDescription],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(ComplaintType::Accident, $complaint->fresh()->complaint_type);
    }

    public function test_update_rejects_invalid_complaint_type(): void
    {
        $complaint = Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->put(route('complaints.update', $complaint), [
                'bus_id' => $this->bus->id,
                'yer' => 'garage',
                'status' => 'pending',
                'complaint_type' => 'not_a_real_type',
                'complaints' => [$this->validDescription],
            ]);

        $response->assertSessionHasErrors('complaint_type');
        $this->assertEquals(ComplaintType::Breakdown, $complaint->fresh()->complaint_type);
    }
}
