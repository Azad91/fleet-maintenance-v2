<?php

namespace Tests\Feature\Imports;

use App\Imports\BusDailyStatusesImport;
use App\Models\Bus;
use App\Models\BusDailyStatus;
use App\Models\Company;
use App\Models\Garage;
use App\Services\GarageContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusDailyStatusesImportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);
        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => 'IMP-001',
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function makeImport(): BusDailyStatusesImport
    {
        return new BusDailyStatusesImport($this->garage->id, $this->company->id);
    }

    protected function invokeResolveDate(mixed $value): string
    {
        $import = $this->makeImport();
        $reflection = new \ReflectionMethod($import, 'resolveDate');
        $reflection->setAccessible(true);

        return $reflection->invoke($import, $value);
    }

    // ==================================================================
    // 1. Date format resolution
    // ==================================================================

    public function test_resolves_dot_separated_day_first_date(): void
    {
        $this->assertSame('2026-09-15', $this->invokeResolveDate('15.09.2026'));
    }

    public function test_resolves_dot_separated_single_digit_day(): void
    {
        $this->assertSame('2026-09-05', $this->invokeResolveDate('5.09.2026'));
    }

    public function test_resolves_slash_separated_date(): void
    {
        $this->assertSame('2026-09-15', $this->invokeResolveDate('15/09/2026'));
    }

    public function test_resolves_iso_format(): void
    {
        $this->assertSame('2026-09-15', $this->invokeResolveDate('2026-09-15'));
    }

    public function test_resolves_excel_serial_number(): void
    {
        $this->assertSame('2023-03-15', $this->invokeResolveDate(45000));
    }

    public function test_resolves_datetime_object(): void
    {
        $this->assertSame(
            '2026-09-15',
            $this->invokeResolveDate(new \DateTime('2026-09-15'))
        );
    }

    public function test_empty_value_defaults_to_today(): void
    {
        $this->assertSame(now()->toDateString(), $this->invokeResolveDate(null));
        $this->assertSame(now()->toDateString(), $this->invokeResolveDate(''));
    }

    public function test_invalid_string_defaults_to_today(): void
    {
        $this->assertSame(now()->toDateString(), $this->invokeResolveDate('not-a-date'));
    }

    // ==================================================================
    // 2. Full-row integration via collection()
    // ==================================================================
    // The import class uses ToCollection (bulk upsert) instead of
    // ToModel — see the class docblock. Tests must feed rows as an
    // array of associative arrays, exactly as WithHeadingRow yields
    // them.

    public function test_collection_creates_status_with_provided_date(): void
    {
        $import = $this->makeImport();

        $import->collection(collect([
            ['dqn' => 'IMP-001', 'date' => '15.09.2026', 'status' => 'READY FOR ROUTE'],
        ]));

        $status = BusDailyStatus::withoutGlobalScopes()
            ->where('bus_id', $this->bus->id)
            ->first();

        $this->assertNotNull($status);
        $this->assertSame('2026-09-15', Carbon::parse($status->date)->toDateString());
        $this->assertSame('READY FOR ROUTE', $status->status);
    }

    public function test_collection_creates_status_for_each_day_separately(): void
    {
        $import = $this->makeImport();

        $import->collection(collect([
            ['dqn' => 'IMP-001', 'date' => '15.09.2026', 'status' => 'READY FOR ROUTE'],
            ['dqn' => 'IMP-001', 'date' => '16.09.2026', 'status' => 'IN MAINTENANCE'],
        ]));

        $this->assertSame(
            2,
            BusDailyStatus::withoutGlobalScopes()->where('bus_id', $this->bus->id)->count()
        );
    }

    public function test_collection_updates_existing_row_on_same_date(): void
    {
        $import = $this->makeImport();

        $import->collection(collect([
            ['dqn' => 'IMP-001', 'date' => '15.09.2026', 'status' => 'READY FOR ROUTE'],
        ]));

        $import->collection(collect([
            ['dqn' => 'IMP-001', 'date' => '15.09.2026', 'status' => 'IN MAINTENANCE'],
        ]));

        $this->assertSame(
            1,
            BusDailyStatus::withoutGlobalScopes()->where('bus_id', $this->bus->id)->count()
        );

        $status = BusDailyStatus::withoutGlobalScopes()
            ->where('bus_id', $this->bus->id)
            ->first();

        $this->assertSame('IN MAINTENANCE', $status->status);
    }
}
