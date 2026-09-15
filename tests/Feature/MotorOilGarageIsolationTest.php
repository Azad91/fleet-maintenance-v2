<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\MotorOilDetail;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotorOilGarageIsolationTest extends TestCase
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

    private function makeDetail(Garage $garage, string $partCode, int $km): MotorOilDetail
    {
        GarageContext::set($garage->id, $garage->company_id);

        return MotorOilDetail::create([
            'part_code' => $partCode,
            'part_name' => "Part {$partCode}",
            'unit' => 'litr',
            'quantity' => 5,
            'km' => $km,
            'count' => 1,
        ]);
    }

    public function test_garage_a_cannot_see_garage_b_motor_oil(): void
    {
        $this->makeDetail($this->garageA, 'A-001', 36000);
        $this->makeDetail($this->garageB, 'B-001', 72000);

        GarageContext::set($this->garageA->id, $this->company->id);

        $visible = MotorOilDetail::all();

        $this->assertCount(1, $visible);
        $this->assertSame('A-001', $visible->first()->part_code);
    }

    public function test_garage_id_is_auto_populated_on_create(): void
    {
        $detail = $this->makeDetail($this->garageA, 'A-002', 36000);

        $this->assertSame($this->garageA->id, $detail->garage_id);
        $this->assertSame($this->company->id, $detail->company_id);
    }

    public function test_create_without_garage_context_throws_exception(): void
    {
        GarageContext::clear();

        $this->expectException(\App\Exceptions\MissingGarageContextException::class);

        MotorOilDetail::create([
            'part_code' => 'NO-CTX',
            'part_name' => 'No Context',
            'unit' => 'litr',
            'quantity' => 1,
            'km' => 1000,
            'count' => 1,
        ]);
    }

    public function test_same_part_code_can_exist_in_different_garages(): void
    {
        $this->makeDetail($this->garageA, 'SHARED', 36000);
        $this->makeDetail($this->garageB, 'SHARED', 72000);

        GarageContext::clear();

        $total = MotorOilDetail::withoutGlobalScopes()
            ->where('part_code', 'SHARED')
            ->count();

        $this->assertSame(2, $total, 'Same part_code must be allowed in different garages');
    }
}
