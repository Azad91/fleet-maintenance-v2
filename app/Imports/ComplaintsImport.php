<?php

namespace App\Imports;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Enums\Location;
use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Employee;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Imports complaints from an Excel file.
 *
 * ── GROUPING MODEL ──────────────────────────────────────────────────
 * Rows sharing the same `bus_dqn` (or following a row with an empty
 * `bus_dqn`) form one complaint card.
 *
 * ── ATOMICITY ───────────────────────────────────────────────────────
 * Every row runs in its own DB transaction. A failure rolls back the
 * entire row — no partial state remains.
 *
 * ── PERFORMANCE (memoization) ───────────────────────────────────────
 * Naive implementations issue 4-5 queries PER ROW:
 *   - one bus lookup
 *   - up to four employee lookups (code, full name, partial name)
 *   - one warehouse lookup
 *
 * For a 1000-row file that is 5000+ round trips, which reliably
 * exceeds PHP's 30-second execution limit.
 *
 * This implementation caches every lookup by its key inside the
 * importer instance. The same employee / warehouse / bus is resolved
 * exactly once, regardless of how many rows reference it. In practice
 * that reduces a 1000-row import from ~5000 queries to ~150.
 *
 * Caches survive across the whole import run (they are instance
 * properties, not per-row locals) and include negative results
 * (lookups that returned null).
 */
class ComplaintsImport extends AbstractImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /** The complaint currently being built. NULL before the first row
     *  or after a failed card header. */
    protected ?Complaint $currentComplaint = null;

    /** DQN of the current card — used to detect new cards. */
    protected ?string $currentDqn = null;

    // ────────────────────────────────────────────────────────────────
    // MEMOIZATION CACHES
    //
    // Keyed by the lookup string (lowercased where relevant). Values
    // are either the resolved model ID or null (negative cache).
    // ────────────────────────────────────────────────────────────────

    /** @var array<string, int|null>  key: lowercased DQN */
    protected array $busCache = [];

    /** @var array<string, int|null>  key: normalized employee query */
    protected array $employeeCache = [];

    /** @var array<string, Warehouse|null>  key: "garageId|code" */
    protected array $warehouseCache = [];

    /**
     * @param  int|null  $garageId  Positive for tenant imports.
     * @param  int|null  $companyId  Optional, used for strict company scoping.
     * @param  bool  $deductStock  When false, details are saved as
     *                             'historical' and stock is never touched.
     */
    public function __construct(
        ?int $garageId = null,
        ?int $companyId = null,
        public readonly bool $deductStock = true,
    ) {
        parent::__construct($garageId, $companyId);
    }

    /**
     * Process the entire file in one pass — no chunking.
     *
     * Chunking would break the grouping rule (a card can span chunks),
     * so we accept the memory cost. Memoization keeps the query count
     * low even for large files.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->processRow($row->toArray(), $this->nextRowIndex());
        }
    }

    /**
     * Process a single import row.
     */
    public function processRow(array $rowArray, int $rowIndex): void
    {
        $dqn = trim((string) ($rowArray['bus_dqn'] ?? $rowArray['dqn'] ?? ''));

        if ($dqn === '' && $this->currentComplaint === null) {
            $this->recordSkip($rowIndex, '—', __('messages.imports.reasons.dqn_missing_in_row'));

            return;
        }

        if ($this->garageId <= 0) {
            throw new \RuntimeException(
                'ComplaintsImport cannot run without a valid garage context.'
            );
        }

        $isNewCard = ($dqn !== '' && $dqn !== $this->currentDqn);

        if (! $isNewCard && $this->currentComplaint === null) {
            $this->recordSkip(
                $rowIndex,
                $dqn ?: ($this->currentDqn ?? '—'),
                __('messages.imports.reasons.previous_row_failed')
            );

            return;
        }

        $bus = null;

        if ($isNewCard) {
            $bus = $this->resolveBus($dqn);

            if (! $bus) {
                $this->currentComplaint = null;
                $this->currentDqn = null;

                $this->recordSkip($rowIndex, $dqn, __('messages.imports.reasons.dqn_not_found'));

                return;
            }

            $this->currentDqn = $dqn;
        }

        try {
            $complaint = DB::transaction(function () use ($rowArray, $bus, $isNewCard) {
                $complaint = $isNewCard
                    ? $this->createComplaint($rowArray, $bus)
                    : $this->currentComplaint;

                $this->attachRowData($complaint, $rowArray);

                return $complaint;
            });

            $this->currentComplaint = $complaint;
            $this->incrementImported();
        } catch (RowSkippedException $e) {
            if ($isNewCard) {
                $this->currentComplaint = null;
            }

            $this->recordSkip(
                $rowIndex,
                $dqn ?: ($this->currentDqn ?? '—'),
                $e->getMessage()
            );
        }
    }

    /**
     * Resolve a Bus by DQN, memoized.
     */
    protected function resolveBus(string $dqn): ?Bus
    {
        $key = mb_strtolower($dqn);

        if (array_key_exists($key, $this->busCache)) {
            $cachedId = $this->busCache[$key];

            return $cachedId === null ? null : Bus::withoutGlobalScopes()->find($cachedId);
        }

        $bus = Bus::withoutGlobalScopes()
            ->where('dqn', $dqn)
            ->where('garage_id', $this->garageId)
            ->first();

        $this->busCache[$key] = $bus?->id;

        return $bus;
    }

    /**
     * Create the complaint header from the FIRST row of a card.
     */
    protected function createComplaint(array $rowArray, Bus $bus): Complaint
    {
        return Complaint::create([
            'garage_id' => $this->garageId,
            'company_id' => $this->companyId ?? $bus->company_id,
            'bus_id' => $bus->id,
            'yer' => $this->normalizeLocation($rowArray['yer'] ?? null),
            'driver_name' => $rowArray['driver_name'] ?? null,
            'complaint_type' => $this->normalizeComplaintType($rowArray['complaint_type'] ?? null),
            'reported_date' => $this->normalizeDate($rowArray['reported_date'] ?? null),
            'reported_time' => $this->normalizeTime($rowArray['reported_time'] ?? null),
            'start_date' => $this->normalizeDate($rowArray['start_date'] ?? null),
            'start_time' => $this->normalizeTime($rowArray['start_time'] ?? null),
            'end_date' => $this->normalizeDate($rowArray['end_date'] ?? null),
            'end_time' => $this->normalizeTime($rowArray['end_time'] ?? null),
            'status' => $this->normalizeStatus($rowArray['status'] ?? null),
            'km' => $this->resolveKm($rowArray, $bus),
            'work_done_by' => $rowArray['work_done_by'] ?? null,
            'notes' => $rowArray['notes'] ?? null,
        ]);
    }

    /**
     * Resolve the complaint's km value.
     */
    protected function resolveKm(array $rowArray, Bus $bus): ?int
    {
        $raw = $rowArray['km']
            ?? $rowArray['yürüş']
            ?? $rowArray['yurus']
            ?? $rowArray['mileage']
            ?? null;

        if ($raw !== null && $raw !== '') {
            return (int) $raw;
        }

        $latestDailyKm = $bus->dailyKmRecords()
            ->orderByDesc('date')
            ->value('km');

        if ($latestDailyKm !== null) {
            return (int) $latestDailyKm;
        }

        return $bus->km !== null ? (int) $bus->km : null;
    }

    /**
     * Attach a complaint item + detail for a single row.
     *
     * @throws RowSkippedException
     */
    protected function attachRowData(Complaint $complaint, array $rowArray): void
    {
        $garageId = $this->garageId;
        $companyId = $this->companyId;

        $partCode = trim((string) (
            $rowArray['part_code']
            ?? $rowArray['code']
            ?? ''
        ));

        $usedQuantity = (int) (
            $rowArray['used_quantity']
            ?? $rowArray['quantity']
            ?? 0
        );

        $partName = $rowArray['part_name']
            ?? $rowArray['name']
            ?? null;

        $description = trim((string) ($rowArray['complaints'] ?? ''));

        // ── Stock deduction (consumed parts only) ──
        $stockQuantity = 0;
        $priceAtUse = null;

        if ($partCode !== '' && $usedQuantity > 0 && $this->deductStock) {
            $warehouse = $this->resolveWarehouse($partCode);

            if (! $warehouse) {
                throw new RowSkippedException(
                    __('messages.imports.reasons.part_not_found', ['code' => $partCode])
                );
            }

            $partName ??= $warehouse->name;

            if ($usedQuantity > $warehouse->quantity) {
                throw new RowSkippedException(__('messages.flash.stock_insufficient', [
                    'name' => $warehouse->name,
                    'requested' => $usedQuantity,
                    'available' => $warehouse->quantity,
                ]));
            }

            $stockQuantity = $warehouse->quantity;
            $priceAtUse = $warehouse->price !== null ? (float) $warehouse->price : null;
            $warehouse->decrement('quantity', $usedQuantity);

            // Update the cache so subsequent rows see the new quantity.
            $this->warehouseCache[$this->warehouseCacheKey($partCode)] = $warehouse->fresh();
        } elseif ($partCode !== '' && $usedQuantity > 0 && ! $this->deductStock) {
            // Historical mode: look up name and price only, never touch quantity.
            $warehouse = $this->resolveWarehouse($partCode);

            if ($warehouse) {
                $partName ??= $warehouse->name;
                $priceAtUse = $warehouse->price !== null ? (float) $warehouse->price : null;
            }
}

        // ── Complaint item ──
        if ($description !== '') {
            $complaint->items()->create([
                'description' => $description,
                'type' => $rowArray['complaint_type'] ?? null,
                'garage_id' => $garageId,
                'company_id' => $companyId,
            ]);
        }

        // ── Detail (0-qty allowed → inspection) ──
        if ($partCode !== '') {
            $sourceType = match (true) {
                ! $this->deductStock  => 'historical',
                $usedQuantity <= 0    => 'inspection',
                default               => 'warehouse',
            };

            $complaint->details()->create([
                'shikayet_index' => 0,
                'code' => $partCode,
                'name' => $partName ?? $partCode,
                'stock_quantity' => $stockQuantity,
                'used_quantity' => max(0, $usedQuantity),
                'price_at_use' => $sourceType === 'inspection' ? 0 : $priceAtUse,   // ← YENİ
                'source_type' => $sourceType,
                'employee_id' => $this->resolveEmployeeId($rowArray),
                'notes' => $rowArray['detail_notes'] ?? $rowArray['notes'] ?? null,
            ]);
        }
    }

    /**
     * Resolve a Warehouse row by part code, memoized.
     *
     * NOTE: this returns the same model instance cached at first
     * lookup. Callers that mutate quantity (deductStock path) must
     * refresh the cache themselves after decrement — see the caller.
     */
    protected function resolveWarehouse(string $code): ?Warehouse
    {
        $key = $this->warehouseCacheKey($code);

        if (array_key_exists($key, $this->warehouseCache)) {
            return $this->warehouseCache[$key];
        }

        $warehouse = Warehouse::withoutGlobalScopes()
            ->where('code', $code)
            ->where('garage_id', $this->garageId)
            ->first();

        $this->warehouseCache[$key] = $warehouse;

        return $warehouse;
    }

    protected function warehouseCacheKey(string $code): string
    {
        return $this->garageId.'|'.$code;
    }

    /**
     * Resolve the employee_id for a detail row, memoized.
     *
     * The name is normalized once, then used as a cache key. Full and
     * partial name lookups run at most once per unique normalized name
     * across the entire import — not once per row.
     */
    protected function resolveEmployeeId(array $rowArray): ?int
    {
        // Explicit numeric id — trust it without caching (it is already
        // the resolved value).
        if (! empty($rowArray['employee_id']) && is_numeric($rowArray['employee_id'])) {
            return (int) $rowArray['employee_id'];
        }

        $rawName = trim((string) (
            $rowArray['employee']
            ?? $rowArray['employee_name']
            ?? $rowArray['employee_code']
            ?? $rowArray['işçi']
            ?? $rowArray['isci']
            ?? $rowArray['usta']
            ?? $rowArray['worker']
            ?? ''
        ));

        if ($rawName === '') {
            return null;
        }

        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', $rawName) ?? $rawName);

        if (array_key_exists($normalized, $this->employeeCache)) {
            return $this->employeeCache[$normalized];
        }

        $resolvedId = $this->lookupEmployee($rawName);
        $this->employeeCache[$normalized] = $resolvedId;

        return $resolvedId;
    }

    /**
     * Perform the actual employee lookup (4 progressive strategies).
     */
    protected function lookupEmployee(string $name): ?int
    {
        $base = Employee::withoutGlobalScopes()
            ->where('garage_id', $this->garageId)
            ->whereNull('deleted_at');

        // 1. Code match (exact, case-insensitive).
        $byCode = (clone $base)
            ->whereRaw('LOWER(code) = LOWER(?)', [$name])
            ->value('id');

        if ($byCode) {
            return (int) $byCode;
        }

        // Azerbaijani/Turkish → ASCII map for both sides.
        $charMap  = 'əƏıİşŞçÇüÜöÖğĞ';
        $asciiMap = 'eEiIsScCuUoOgG';

        $columnNorm = "LOWER(TRANSLATE(first_name || ' ' || COALESCE(last_name, ''), '{$charMap}', '{$asciiMap}'))";
        $inputNorm  = "LOWER(TRANSLATE(?, '{$charMap}', '{$asciiMap}'))";

        // 2. Full name match (normalized).
        $byFullName = (clone $base)
            ->whereRaw("{$columnNorm} = {$inputNorm}", [$name])
            ->value('id');

        if ($byFullName) {
            return (int) $byFullName;
        }

        // 3. Partial first-name match — last resort.
        $firstName = explode(' ', $name)[0] ?? null;

        if ($firstName && mb_strlen($firstName) >= 3) {
            $firstNameNorm = "LOWER(TRANSLATE(first_name, '{$charMap}', '{$asciiMap}'))";

            $byFirstName = (clone $base)
                ->whereRaw("{$firstNameNorm} LIKE {$inputNorm}", [$firstName.'%'])
                ->value('id');

            if ($byFirstName) {
                return (int) $byFirstName;
            }
        }

        return null;
    }

    // ────────────────────────────────────────────────────────────────
    // NORMALIZERS
    // ────────────────────────────────────────────────────────────────

    protected function normalizeLocation(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, Location::values(), true) ? $value : null;
    }

    protected function normalizeComplaintType(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ComplaintType::values(), true) ? $value : null;
    }

    protected function normalizeStatus(mixed $value): string
    {
        if ($value === null || $value === '') {
            return ComplaintStatus::Pending->value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ComplaintStatus::values(), true)
            ? $value
            : ComplaintStatus::Pending->value;
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 100000) {
            try {
                return Carbon::instance(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)
                )->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $value, $m)) {
            try {
                return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->toDateString();
            } catch (\Throwable $e) {
                // fall through
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('H:i');
        }

        $value = trim((string) $value);

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'bus_dqn' => 'sometimes|nullable',
            'dqn' => 'sometimes|nullable',
            'status' => ['nullable', 'string'],
            'yer' => ['nullable', 'string'],
            'complaint_type' => ['nullable', 'string'],
            'km' => 'nullable|integer|min:0',
        ];
    }
}
