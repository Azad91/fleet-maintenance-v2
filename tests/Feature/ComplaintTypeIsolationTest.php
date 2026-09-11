<?php

namespace Tests\Feature;

use App\Models\ComplaintType;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintTypeIsolationTest extends TestCase
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

    private function garageSession(): array
    {
        return [
            'current_garage_id'  => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ];
    }

    public function test_admin_sees_only_own_garage_types(): void
    {
        ComplaintType::withoutGlobalScopes()->create([
            'name'       => 'Type A',
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
        ]);

        ComplaintType::withoutGlobalScopes()->create([
            'name'       => 'Type B',
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->adminA)
            ->withSession($this->garageSession())
            ->get(route('complaint-types.index'));

        $response->assertOk();
        $response->assertSee('Type A');
        $response->assertDontSee('Type B');
    }

    public function test_admin_cannot_edit_type_from_other_garage(): void
    {
        $otherType = ComplaintType::withoutGlobalScopes()->create([
            'name'       => 'Other Garage Type',
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->adminA)
            ->withSession($this->garageSession())
            ->get(route('complaint-types.edit', $otherType));

        // Global scope hides it → 404
        $response->assertStatus(404);
    }

    public function test_admin_cannot_delete_type_from_other_garage(): void
    {
        $otherType = ComplaintType::withoutGlobalScopes()->create([
            'name'       => 'Other Garage Type',
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->adminA)
            ->withSession($this->garageSession())
            ->delete(route('complaint-types.destroy', $otherType));

        $response->assertStatus(404);

        // The other garage's type still exists
        $this->assertDatabaseHas('complaint_types', [
            'id'   => $otherType->id,
            'name' => 'Other Garage Type',
        ]);
    }

    public function test_same_name_allowed_in_different_garages(): void
    {
        ComplaintType::withoutGlobalScopes()->create([
            'name'       => 'Same Name',
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
        ]);

        // Same name in a different garage — should be allowed
        ComplaintType::withoutGlobalScopes()->create([
            'name'       => 'Same Name',
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertEquals(2, ComplaintType::withoutGlobalScopes()
            ->where('name', 'Same Name')
            ->count());
    }

    public function test_duplicate_name_rejected_in_same_garage(): void
    {
        ComplaintType::withoutGlobalScopes()->create([
            'name'       => 'Duplicate',
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->adminA)
            ->withSession($this->garageSession())
            ->post(route('complaint-types.store'), ['name' => 'Duplicate']);

        $response->assertSessionHasErrors('name');
    }
}
