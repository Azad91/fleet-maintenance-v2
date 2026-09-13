<?php

namespace Tests\Feature\Imports;

use App\Imports\AbstractImport;
use App\Imports\BusesImport;
use App\Imports\ComplaintsImport;
use App\Imports\DriversImport;
use App\Imports\EmployeesImport;
use App\Imports\WarehouseImport;
use Tests\TestCase;

class AbstractImportTest extends TestCase
{
    // ==================================================================
    // 1. CONSTRUCTOR GUARD — bütün subclass-lar üçün
    // ==================================================================

    public function test_all_imports_reject_zero_garage_id(): void
    {
        $classes = [
            BusesImport::class,
            ComplaintsImport::class,
            DriversImport::class,
            EmployeesImport::class,
            WarehouseImport::class,
        ];

        foreach ($classes as $class) {
            try {
                new $class(0);
                $this->fail("{$class} must reject garage_id = 0");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('requires a valid garage id', $e->getMessage());
            }
        }
    }

    public function test_all_imports_reject_negative_garage_id(): void
    {
        $classes = [
            BusesImport::class,
            ComplaintsImport::class,
            DriversImport::class,
            EmployeesImport::class,
            WarehouseImport::class,
        ];

        foreach ($classes as $class) {
            try {
                new $class(-1);
                $this->fail("{$class} must reject negative garage_id");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('requires a valid garage id', $e->getMessage());
            }
        }
    }

    public function test_all_imports_accept_positive_garage_id(): void
    {
        $classes = [
            BusesImport::class,
            ComplaintsImport::class,
            DriversImport::class,
            EmployeesImport::class,
            WarehouseImport::class,
        ];

        foreach ($classes as $class) {
            $import = new $class(5, 10);
            $this->assertSame(5, $import->garageId);
            $this->assertSame(10, $import->companyId);
        }
    }

    // ==================================================================
    // 2. DEFAULT CHUNK SIZE
    // ==================================================================

    public function test_default_chunk_size_is_100(): void
    {
        $import = new BusesImport(1);

        $this->assertSame(100, $import->chunkSize());
    }

    // ==================================================================
    // 3. INITIAL STATE
    // ==================================================================

    public function test_skipped_and_imported_counters_start_empty(): void
    {
        $import = new BusesImport(1);

        $this->assertSame([], $import->skipped);
        $this->assertSame(0, $import->importedCount);
    }

    // ==================================================================
    // 4. recordSkip SHAPE
    // ==================================================================

    public function test_record_skip_stores_expected_shape(): void
    {
        $import = new TestableImport(1);

        $import->exposedRecordSkip(5, 'ABC-123', 'Test reason');

        $this->assertCount(1, $import->skipped);
        $this->assertSame(5, $import->skipped[0]['row']);
        $this->assertSame('ABC-123', $import->skipped[0]['dqn']);
        $this->assertSame('Test reason', $import->skipped[0]['reason']);
    }

    public function test_record_skip_replaces_empty_identifier_with_dash(): void
    {
        $import = new TestableImport(1);

        $import->exposedRecordSkip(3, '', 'Empty id');

        $this->assertSame('—', $import->skipped[0]['dqn']);
    }

    public function test_record_skip_accumulates_multiple_entries(): void
    {
        $import = new TestableImport(1);

        $import->exposedRecordSkip(2, 'A', 'Reason 1');
        $import->exposedRecordSkip(5, 'B', 'Reason 2');
        $import->exposedRecordSkip(8, 'C', 'Reason 3');

        $this->assertCount(3, $import->skipped);
    }

    // ==================================================================
    // 5. incrementImported
    // ==================================================================

    public function test_increment_imported_by_one(): void
    {
        $import = new TestableImport(1);

        $import->exposedIncrementImported();
        $import->exposedIncrementImported();
        $import->exposedIncrementImported();

        $this->assertSame(3, $import->importedCount);
    }

    public function test_increment_imported_by_n(): void
    {
        $import = new TestableImport(1);

        $import->exposedIncrementImported(5);
        $import->exposedIncrementImported(3);

        $this->assertSame(8, $import->importedCount);
    }

    // ==================================================================
    // 6. nextRowIndex
    // ==================================================================

    public function test_next_row_index_starts_at_two(): void
    {
        $import = new TestableImport(1);

        // First row is row 2 (row 1 is the header).
        $this->assertSame(2, $import->exposedNextRowIndex());
        $this->assertSame(3, $import->exposedNextRowIndex());
        $this->assertSame(4, $import->exposedNextRowIndex());
    }
}

/**
 * Minimal concrete subclass exposing protected members for testing.
 */
class TestableImport extends AbstractImport
{
    public function exposedRecordSkip(int|string $row, string $identifier, string $reason): void
    {
        $this->recordSkip($row, $identifier, $reason);
    }

    public function exposedIncrementImported(int $by = 1): void
    {
        $this->incrementImported($by);
    }

    public function exposedNextRowIndex(): int
    {
        return $this->nextRowIndex();
    }
}
