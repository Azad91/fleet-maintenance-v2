<?php

namespace Tests\Feature;

use App\Exports\DriversExport;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_download_drivers_excel_export()
    {
        Excel::fake();

        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => 'super_admin']);

        Driver::create([
            'garage_id'  => $garage->id,
            'company_id' => $company->id,
            'code'       => 'DRV-777',
            'first_name' => 'Driver',
            'last_name'  => 'Testov',
            'is_active'  => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id
            ])
            ->get('/drivers/export');

        $response->assertStatus(200);

        Excel::assertDownloaded('drivers.xlsx', function (DriversExport $export) {
            return $export->collection()->contains('code', 'DRV-777');
        });
    }
}
