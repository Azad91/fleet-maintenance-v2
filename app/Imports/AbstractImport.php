<?php

namespace App\Imports;

/**
 * Base class for all Excel imports.
 *
 * Centralizes:
 *   - garage / company context validation (constructor guard)
 *   - skip tracking (recordSkip helper)
 *   - import count tracking
 *   - a consistent chunk size (100)
 *
 * Subclasses only need to implement their row-handling logic and call
 * recordSkip() / incrementImported() as appropriate.
 *
 * The constructor accepts a nullable garage id:
 *
 *   - A positive value means "tenant-scoped import" — the import will
 *     be filtered by that garage, and no row can touch another tenant.
 *   - null means "global-catalog import" — used by MotorOilImport,
 *     whose target table (motor_oil_details) has no garage_id column.
 *     The garage guard does not apply.
 *
 * Zero and negative values are still rejected so that no tenant-scoped
 * import can accidentally run without a valid garage context.
 */
abstract class AbstractImport
{
    /** @var array<int, array{row: int|string, dqn: string, reason: string}> */
    public array $skipped = [];

    public int $importedCount = 0;

    /**
     * 1-based counter of rows processed in this import. Unlike the
     * file's actual row number, this is deterministic across chunks
     * and headings, and is what we report in skip messages.
     */
    protected int $rowCounter = 0;

    /**
     * @param  int|null  $garageId  Positive for tenant imports, null for global catalogs.
     * @param  int|null  $companyId  Optional, used for strict company scoping.
     *
     * @throws \InvalidArgumentException when a garage id is provided but not positive.
     */
    public function __construct(
        public readonly ?int $garageId = null,
        public readonly ?int $companyId = null,
    ) {
        if ($garageId !== null && $garageId <= 0) {
            throw new \InvalidArgumentException(sprintf(
                '%s requires a valid garage id (> 0) when one is provided. Got [%d]. '
                .'Pass null for global-catalog imports (e.g. MotorOilImport).',
                static::class,
                $garageId,
            ));
        }
    }

    /**
     * Number of rows to read per chunk. Override in subclasses if a
     * specific import needs a different value, but 100 is a good
     * default for the typical 10-50k row Excel files we ingest.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Increment the per-import row counter and return the 1-based
     * index of the current row (accounting for the header row).
     *
     * Call this at the top of every row handler, even for rows that
     * will be skipped — it keeps the reported row numbers consistent.
     */
    protected function nextRowIndex(): int
    {
        $this->rowCounter++;

        return $this->rowCounter + 1; // +1 for the header row
    }

    /**
     * Record a skipped row. The shape is uniform across all imports
     * so the import report view can render any import's skips with
     * the same markup.
     */
    protected function recordSkip(int|string $row, string $identifier, string $reason): void
    {
        $this->skipped[] = [
            'row' => $row,
            'dqn' => $identifier !== '' ? $identifier : '—',
            'reason' => $reason,
        ];
    }

    /**
     * Increment the imported counter by 1 (or by $by when a single
     * source row produces multiple records — e.g. MotorOilImport).
     */
    protected function incrementImported(int $by = 1): void
    {
        $this->importedCount += $by;
    }

    /**
     * Accessor for the parent row counter — used by tests and by
     * processRow()-style methods that live in subclasses.
     */
    public function getRowCounter(): int
    {
        return $this->rowCounter;
    }
}
