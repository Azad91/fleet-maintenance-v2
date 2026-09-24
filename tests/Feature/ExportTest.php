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
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'code' => 'DRV-777',
            'first_name' => 'Driver',
            'last_name' => 'Testov',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id,
            ])
            ->get('/drivers/export');

        $response->assertStatus(200);

        Excel::assertDownloaded('drivers.xlsx', function (DriversExport $export) {
            return $export->collection()->contains('code', 'DRV-777');
        });
    }

    public function test_drivers_export_sanitizes_formula_injection(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => 'super_admin']);

        // Driver with a malicious code that would become a formula in Excel
        Driver::withoutGlobalScopes()->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'code' => 'DRV-SAFE-1',
            'first_name' => '=cmd|\'/c calc\'!A1', // ← Formula injection attempt
            'last_name' => '@SUM(1+1)',
            'notes' => '+HYPERLINK("http://evil.com","click")',
            'is_active' => true,
        ]);

        $export = new DriversExport;
        $row = $export->map(Driver::withoutGlobalScopes()
            ->where('code', 'DRV-SAFE-1')
            ->first());

        // Values starting with dangerous characters must be prefixed with '
        $this->assertSame("'=cmd|'/c calc'!A1", $row[1]);
        $this->assertSame("'@SUM(1+1)", $row[2]);
        $this->assertSame("'+HYPERLINK(\"http://evil.com\",\"click\")", $row[6]);

        // Safe values must remain untouched
        $this->assertSame('DRV-SAFE-1', $row[0]);
    }
}
