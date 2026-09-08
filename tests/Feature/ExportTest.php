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
        // Excel feyk (fake) edilir ki, real fayl sistemi yüklənməsin
        Excel::fake();

        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => 'super_admin']);

        // Test üçün bazaya bir sürücü əlavə edirik
        Driver::create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'code' => 'DRV-777',
            'first_name' => 'Sürücü',
            'last_name' => 'Testov',
            'is_active' => true,
        ]);

        // Export URL-nə GET sorğusu göndəririk
        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id
            ])
            ->get('/drivers/export');

        $response->assertStatus(200);

        // Sistemin doğrudan da DriversExport sinfini çağırıb yüklədiyini və fayl adını yoxlayırıq
        Excel::assertDownloaded('suruculer.xlsx', function (DriversExport $export) {
            return $export->collection()->contains('code', 'DRV-777');
        });
    }
}
