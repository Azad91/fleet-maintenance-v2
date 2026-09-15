<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\ServiceTemplate;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTemplateGarageIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected function setUp(): void
    {
        parent::setUp();

        GarageContext::clear();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function makeTemplate(Garage $garage, string $name, int $km): ServiceTemplate
    {
        GarageContext::set($garage->id, $garage->company_id);

        return ServiceTemplate::create([
            'name' => $name,
            'default_km_interval' => $km,
            'details' => [],
        ]);
    }

    public function test_garage_a_cannot_see_garage_b_templates(): void
    {
        $this->makeTemplate($this->garageA, 'Garage A Template', 36000);
        $this->makeTemplate($this->garageB, 'Garage B Template', 72000);

        GarageContext::set($this->garageA->id, $this->company->id);

        $visible = ServiceTemplate::all();

        $this->assertCount(1, $visible);
        $this->assertSame('Garage A Template', $visible->first()->name);
    }

    public function test_garage_id_is_auto_populated_on_create(): void
    {
        $template = $this->makeTemplate($this->garageA, 'Template X', 36000);

        $this->assertSame($this->garageA->id, $template->garage_id);
        $this->assertSame($this->company->id, $template->company_id);
    }

    public function test_create_without_garage_context_throws_exception(): void
    {
        GarageContext::clear();

        $this->expectException(\App\Exceptions\MissingGarageContextException::class);

        ServiceTemplate::create([
            'name' => 'No Context',
            'default_km_interval' => 36000,
            'details' => [],
        ]);
    }
}
