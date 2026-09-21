<?php

namespace Tests\Feature\OilChange;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusOilChange;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OilChangeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garage;
    protected User $admin;
    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage  = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'uzunluq'    => 12,
            'is_active'  => true,
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function garageSession(): array
    {
        return [
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    // ==================================================================
    // 1. STORE — HAPPY PATH
    // ==================================================================

    public function test_can_create_motor_oil_change(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('oil-changes.store'), [
                'bus_id'     => $this->bus->id,
                'oil_type'   => OilType::Motor->value,
                'actual_km'  => 100000,
                'changed_at' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('oil-changes.show', $this->bus));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bus_oil_changes', [
            'bus_id'   => $this->bus->id,
            'oil_type' => OilType::Motor->value,
            'actual_km' => 100000,
        ]);
    }

    // ==================================================================
    // 2. STORE — GEARBOX REQUIRES OIL_BRAND (P1 FIX VERIFICATION)
    // ==================================================================

    /**
     * ✅ Bu test P1 bug-unu təsdiqləyir.
     *
     * Əvvəl: app(OilChangeStoreRequest::class)->rules() çağırıldığı üçün
     * Rule::requiredIf(fn () => $this->input('oil_type') === 'gearbox')
     * closure-u işləmirdi və oil_brand MƏCBURİ DEYİLDİ.
     *
     * İndi: FormRequest injection → closure düzgün işləyir → validation
     * xətası qaytarır.
     */
    public function test_gearbox_requires_oil_brand(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('oil-changes.store'), [
                'bus_id'    => $this->bus->id,
                'oil_type'  => OilType::Gearbox->value,
                'actual_km' => 100000,
                // oil_brand yoxdur — MÜTLƏQ xəta verməlidir
            ]);

        $response->assertSessionHasErrors('oil_brand');

        // ✅ FIX: assertDatabaseMissing() RedirectResponse-də deyil,
        // TestCase üzərində çağırılır.
        $this->assertDatabaseMissing('bus_oil_changes', [
            'bus_id'   => $this->bus->id,
            'oil_type' => OilType::Gearbox->value,
        ]);
    }

    public function test_gearbox_succeeds_with_oil_brand(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('oil-changes.store'), [
                'bus_id'    => $this->bus->id,
                'oil_type'  => OilType::Gearbox->value,
                'oil_brand' => 'shell',
                'actual_km' => 100000,
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bus_oil_changes', [
            'bus_id'    => $this->bus->id,
            'oil_type'  => OilType::Gearbox->value,
            'oil_brand' => 'SHELL', // prepareForValidation() → uppercase
        ]);
    }

    // ==================================================================
    // 3. STORE — PREPARE FOR VALIDATION (P1 FIX VERIFICATION)
    // ==================================================================

    /**
     * ✅ prepareForValidation() indi işləyir → oil_brand UPPERCASE olur.
     */
    public function test_oil_brand_is_normalized_to_uppercase(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('oil-changes.store'), [
                'bus_id'    => $this->bus->id,
                'oil_type'  => OilType::Gearbox->value,
                'oil_brand' => '  shell  ', // lowercase + whitespace
                'actual_km' => 100000,
            ]);

        $this->assertDatabaseHas('bus_oil_changes', [
            'oil_brand' => 'SHELL', // trim + uppercase
        ]);
    }

    // ==================================================================
    // 4. STORE — VALIDATION
    // ==================================================================

    public function test_actual_km_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('oil-changes.store'), [
                'bus_id'   => $this->bus->id,
                'oil_type' => OilType::Motor->value,
            ]);

        $response->assertSessionHasErrors('actual_km');
    }

    public function test_actual_km_must_be_positive(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('oil-changes.store'), [
                'bus_id'    => $this->bus->id,
                'oil_type'  => OilType::Motor->value,
                'actual_km' => -500,
            ]);

        $response->assertSessionHasErrors('actual_km');
    }

    public function test_oil_type_must_be_valid(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('oil-changes.store'), [
                'bus_id'    => $this->bus->id,
                'oil_type'  => 'transmission',
                'actual_km' => 100000,
            ]);

        $response->assertSessionHasErrors('oil_type');
    }

    // ==================================================================
    // 5. UPDATE
    // ==================================================================

    public function test_can_update_oil_change(): void
    {
        $change = BusOilChange::withoutGlobalScopes()->create([
            'bus_id'      => $this->bus->id,
            'garage_id'   => $this->garage->id,
            'company_id'  => $this->company->id,
            'oil_type'    => OilType::Motor->value,
            'actual_km'   => 100000,
            'interval_km' => 36000,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->put(route('oil-changes.update', $change), [
                'bus_id'    => $this->bus->id,
                'oil_type'  => OilType::Motor->value,
                'actual_km' => 110000,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(110000, $change->fresh()->actual_km);
    }

    public function test_update_gearbox_requires_oil_brand(): void
    {
        $change = BusOilChange::withoutGlobalScopes()->create([
            'bus_id'      => $this->bus->id,
            'garage_id'   => $this->garage->id,
            'company_id'  => $this->company->id,
            'oil_type'    => OilType::Gearbox->value,
            'oil_brand'   => 'SHELL',
            'actual_km'   => 100000,
            'interval_km' => 180000,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->put(route('oil-changes.update', $change), [
                'bus_id'    => $this->bus->id,
                'oil_type'  => OilType::Gearbox->value,
                // oil_brand yoxdur
                'actual_km' => 110000,
            ]);

        $response->assertSessionHasErrors('oil_brand');
    }

    // ==================================================================
    // 6. DESTROY
    // ==================================================================

    public function test_can_soft_delete_oil_change(): void
    {
        $change = BusOilChange::withoutGlobalScopes()->create([
            'bus_id'      => $this->bus->id,
            'garage_id'   => $this->garage->id,
            'company_id'  => $this->company->id,
            'oil_type'    => OilType::Motor->value,
            'actual_km'   => 100000,
            'interval_km' => 36000,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->delete(route('oil-changes.destroy', $change));

        $response->assertRedirect(route('oil-changes.show', $this->bus->id));
        $this->assertSoftDeleted('bus_oil_changes', ['id' => $change->id]);
    }

    // ==================================================================
    // 7. ACCESS CONTROL
    // ==================================================================

    public function test_guest_cannot_create_oil_change(): void
    {
        $this->post(route('oil-changes.store'), [
            'bus_id'    => $this->bus->id,
            'oil_type'  => OilType::Motor->value,
            'actual_km' => 100000,
        ])->assertRedirect(route('login'));
    }

    public function test_complaint_worker_cannot_create_oil_change(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garage->id, [
            'role' => 'complaint_worker',
            'is_active' => true,
        ]);

        $this->actingAs($worker)
            ->withSession($this->garageSession())
            ->post(route('oil-changes.store'), [
                'bus_id'    => $this->bus->id,
                'oil_type'  => OilType::Motor->value,
                'actual_km' => 100000,
            ])
            ->assertForbidden();
    }
}
