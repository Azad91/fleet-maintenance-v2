<?php

namespace Tests\Feature\OilChange;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusOilChange;
use App\Models\BusBrand;
use App\Models\Company;
use App\Models\Garage;
use App\Models\MotorOilDetail;
use App\Services\GarageContext;
use App\Services\OilChange\OilChangeStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OilChangeCatalogMilestoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_18m_bus_next_catalog_rounds_up_to_nearest_36000_multiple(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        GarageContext::set($garage->id, $company->id);

        $brand = BusBrand::create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'name' => 'BMC',
            'code' => 'BMC',
            'is_active' => true,
        ]);

        // Kataloq 36000-in qatları
        foreach ([36000, 72000, 360000, 396000, 432000] as $km) {
            MotorOilDetail::create([
                'brand_id' => $brand->id,
                'part_code' => "OIL-{$km}",
                'part_name' => "Filter {$km}",
                'unit' => 'piece',
                'quantity' => 1,
                'km' => $km,
                'count' => 1,
            ]);
        }

        $bus = Bus::factory()->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'brand_id' => $brand->id,
            'uzunluq' => 18,
        ]);

        // Son dəyişmə: 360000, interval 30000 → next_due = 390000
        BusOilChange::create([
            'bus_id' => $bus->id,
            'oil_type' => OilType::Motor->value,
            'actual_km' => 360000,
            'interval_km' => 30000,
            'scheduled_km' => 360000,
        ]);

        $service = app(OilChangeStatusService::class);
        $status = $service->forBus($bus->fresh(), OilType::Motor);

        $this->assertSame(390000, $status->nextDueKm);
        $this->assertSame(396000, $status->nextCatalogKm,
            '18m bus with 30000 interval should round up to 396000 in catalog');
    }
}
