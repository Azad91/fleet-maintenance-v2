<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusSearchTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->user = User::factory()->create(['role' => 'user']);
        $this->user->garages()->attach($this->garage->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($this->garage->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    /**
     * Aktual qaraj sessiyası məlumatlarını qaytarır.
     *
     * Qeyd: metodu `session()` adlandırmırıq — bu ad Laravel TestCase-də
     * artıq istifadə olunur və access level konflikti yaradır.
     */
    private function garageSession(): array
    {
        return [
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    public function test_search_returns_full_view_for_normal_request(): void
    {
        Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => '90-AA-111',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->get(route('buses.search', ['dqn' => '90-AA']));

        $response->assertOk();
        $response->assertViewIs('buses.index');
    }

    public function test_search_returns_partial_for_ajax_request(): void
    {
        Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => '90-BB-222',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('buses.search', ['dqn' => '90-BB']));

        $response->assertOk();
        $this->assertStringContainsString('90-BB-222', $response->getContent());
        // Partial — tam HTML səhifə deyil
        $this->assertStringNotContainsString('<!DOCTYPE html>', $response->getContent());
    }

    public function test_search_filters_by_dqn(): void
    {
        Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => '90-AA-111',
        ]);
        Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => '77-XX-999',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('buses.search', ['dqn' => '90-AA']));

        $content = $response->getContent();
        $this->assertStringContainsString('90-AA-111', $content);
        $this->assertStringNotContainsString('77-XX-999', $content);
    }

    public function test_search_filters_by_route_number(): void
    {
        Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'route_number' => '15405',
        ]);
        Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'route_number' => '99999',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('buses.search', ['route_number' => '154']));

        $content = $response->getContent();
        $this->assertStringContainsString('15405', $content);
        $this->assertStringNotContainsString('99999', $content);
    }

    public function test_search_with_no_filters_returns_all_buses(): void
    {
        Bus::factory()->count(3)->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('buses.search'));

        $response->assertOk();
    }

    public function test_search_respects_garage_isolation(): void
    {
        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);

        Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => '90-AA-111',
        ]);
        Bus::factory()->create([
            'garage_id' => $otherGarage->id,
            'company_id' => $this->company->id,
            'dqn' => '90-AA-222',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('buses.search', ['dqn' => '90-AA']));

        $content = $response->getContent();
        $this->assertStringContainsString('90-AA-111', $content);
        $this->assertStringNotContainsString('90-AA-222', $content, 'Other garage buses must not leak');
    }
}
