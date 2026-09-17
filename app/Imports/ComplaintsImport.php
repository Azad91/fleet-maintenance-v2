<?php

namespace App\Imports;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Enums\Location;
use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

/**
 * Imports complaints from an Excel file.
 *
 * ── GROUPING MODEL ──────────────────────────────────────────────────
 * The Excel file groups multiple complaints + multiple details onto a
 * single card using a simple visual rule:
 *
 *   - A row with `bus_dqn` DIFFERENT from the previous row → starts a
 *     NEW complaint card.
 *   - A row with `bus_dqn` EQUAL to the previous row → the row belongs
 *     to the SAME card (adds a new complaint item + a new detail).
 *   - A row with `bus_dqn` EMPTY → the row continues the CURRENT card.
 *
 * Example (12 rows → 7 cards):
 *
 *   99JU261 | ARAÇ STOP EDİYOR.              → card 1
 *   99JU937 | ARAÇTA YAĞ KAÇAĞI VAR.         → card 2
 *   99JU339 | ARAÇTA SU KAÇAĞI VAR.          → card 3
 *   99JU339 | HİDROSTATİK FAN POMPA...       → card 3 (same bus)
 *   99JZ193 | BAKIM KAPAĞI ARIZASI.          → card 4
 *   99JB315 | ARAÇ ÇEKİŞİ DÜŞÜK.             → card 5
 *   (empty) | ARAÇTA SU KAÇAĞI VAR.          → card 5
 *   (empty) | ARAÇTA YAĞ KAÇAĞI VAR.         → card 5
 *   99JU251 | ARAÇTA SU KAÇAĞI VAR.          → card 6
 *   (empty) | (empty) | (empty)              → card 6
 *   99JU372 | ARAÇTA SU KAÇAĞI VAR.          → card 7
 *   (empty) | ARAÇTA YAĞ KAÇAĞI VAR.         → card 7
 *
 * Header fields (bus, date, yer, type, status, work_done_by, notes) are
 * taken from the FIRST row of each card. Subsequent rows may repeat
 * those values (as in the source file) but they are ignored — the
 * first non-empty value wins. This keeps the model consistent and
 * avoids partial updates.
 *
 * ── STOCK ───────────────────────────────────────────────────────────
 * Details are saved per row. When $deductStock is true (default),
 * warehouse stock is deducted from each detail row. When false (for
 * historical imports), no stock is touched.
 */
class ComplaintsImport extends AbstractImport implements SkipsOnFailure, ToCollection, WithHeadingRow
{
    use SkipsFailures;

    /**
     * The complaint currently being built. NULL before the first row.
     */
    protected ?Complaint $currentComplaint = null;

    /**
     * DQN of the current card. Used to decide when a new card starts.
     */
    protected ?string $currentDqn = null;

    /**
     * @param  int|null  $garageId  Positive for tenant imports.
     * @param  int|null  $companyId  Optional, used for strict company scoping.
     * @param  bool  $deductStock  When false, details are saved as
     *                             'historical' and stock is never touched
     *                             — not on import, not on delete, not on
     *                             update. Use for importing past complaints.
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
     * Chunking would break the grouping rule (a card can span rows
     * that fall into different chunks), so we accept the memory cost.
     * For the typical "one month of history" file (~200–500 rows) this
     * is fast and safe.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->processRow($row->toArray(), $this->nextRowIndex());
        }
    }

    /**
     * Process a single import row.
     *
     * Public so it can be tested directly without constructing a
     * Maatwebsite\Excel\Row instance. Called sequentially by
     * collection() with $currentComplaint / $currentDqn carrying
     * state between calls.
     *
     * @param  array<string, mixed>  $rowArray
     */
    public function processRow(array $rowArray, int $rowIndex): void
    {
        // ─── 1. Resolve the bus_dqn (may be empty → continue current) ───
        $dqn = trim((string) ($rowArray['bus_dqn'] ?? $rowArray['dqn'] ?? ''));

        // Detect whether this row starts a new card.
        $isNewCard = false;

        if ($dqn !== '') {
            // A non-empty DQN that differs from the current one starts
            // a new card. A repeated DQN (same bus, same card) is
            // treated as a continuation.
            if ($dqn !== $this->currentDqn) {
                $isNewCard = true;
                $this->currentDqn = $dqn;
            }
        } else {
            // Empty DQN continues the current card. If there is no
            // current card yet, the row cannot be placed anywhere.
            if ($this->currentComplaint === null) {
                $this->recordSkip($rowIndex, '—', __('messages.imports.reasons.dqn_missing_in_row'));

                return;
            }
        }

        // Defensive: the constructor enforces this, but the check
        // protects against future refactors that bypass it.
        if ($this->garageId <= 0) {
            throw new \RuntimeException(
                'ComplaintsImport cannot run without a valid garage context.'
            );
        }

        $garageId = $this->garageId;
        $companyId = $this->companyId;

        // ─── 2. Resolve the bus (only when starting a new card) ───
        if ($isNewCard) {
            $bus = Bus::withoutGlobalScopes()
                ->where('dqn', $dqn)
                ->where('garage_id', $garageId)
                ->first();

            if (! $bus) {
                $this->recordSkip($rowIndex, $dqn, __('messages.imports.reasons.dqn_not_found'));

                // Reset state so the NEXT row with a different DQN
                // starts a fresh card instead of appending to a card
                // that was never created.
                $this->currentComplaint = null;
                $this->currentDqn = null;

                return;
            }

            // Create the card header from this row's data.
            $this->currentComplaint = $this->createComplaint($rowArray, $bus);
        }

        // From this point on, we KNOW we have a valid $currentComplaint.
        /** @var Complaint $complaint */
        $complaint = $this->currentComplaint;

        // ─── 3. Extract the detail data ───
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

        $sourceType = $this->deductStock ? 'warehouse' : 'historical';

        // ─── 4. ATOMIC BLOCK: item + detail + stock ───
        $skipReason = DB::transaction(function () use (
            $rowArray,
            $complaint,
            $garageId,
            $companyId,
            $partCode,
            $usedQuantity,
            $sourceType,
            &$partName
        ) {
            $stockQuantity = 0;

            // 4a. Stock deduction (skipped in historical mode)
            if ($partCode !== '' && $usedQuantity > 0) {
                $warehouse = Warehouse::withoutGlobalScopes()
                    ->where('code', $partCode)
                    ->where('garage_id', $garageId)
                    ->lockForUpdate()
                    ->first();

                if ($warehouse) {
                    $partName ??= $warehouse->name;

                    if ($this->deductStock) {
                        if ($usedQuantity > $warehouse->quantity) {
                            return __('messages.flash.stock_insufficient', [
                                'name' => $warehouse->name,
                                'requested' => $usedQuantity,
                                'available' => $warehouse->quantity,
                            ]);
                        }

                        $stockQuantity = $warehouse->quantity;
                        $warehouse->decrement('quantity', $usedQuantity);
                    }
                } elseif ($this->deductStock) {
                    return __('messages.imports.reasons.part_not_found', ['code' => $partCode]);
                }
            }

            // 4b. Complaint item (one per row)
            $description = trim((string) ($rowArray['complaints'] ?? ''));

            if ($description !== '') {
                $complaint->items()->create([
                    'description' => $description,
                    'type' => $rowArray['complaint_type'] ?? null,
                    'garage_id' => $garageId,
                    'company_id' => $companyId,
                ]);
            }

            // 4c. Detail (one per row, only when part info is present)
            if ($partCode !== '' && $usedQuantity > 0) {
                $complaint->details()->create([
                    'shikayet_index' => 0,
                    'code' => $partCode,
                    'name' => $partName ?? $partCode,
                    'stock_quantity' => $stockQuantity,
                    'used_quantity' => $usedQuantity,
                    'source_type' => $sourceType,
                    'notes' => $rowArray['detail_notes'] ?? $rowArray['notes'] ?? null,
                ]);
            }

            return null;
        });

        if ($skipReason !== null) {
            $this->recordSkip($rowIndex, $dqn ?: $this->currentDqn ?: '—', $skipReason);

            return;
        }

        $this->incrementImported();
    }

    /**
     * Create the complaint header from the FIRST row of a card.
     *
     * Header fields are read once. Subsequent rows in the same card
     * may repeat these values, but they are ignored — the first
     * non-empty value wins. This keeps the card consistent and avoids
     * surprising partial updates.
     *
     * @param  array<string, mixed>  $rowArray
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
            'km' => isset($rowArray['km']) && $rowArray['km'] !== '' ? (int) $rowArray['km'] : null,
            'work_done_by' => $rowArray['work_done_by'] ?? null,
            'notes' => $rowArray['notes'] ?? null,
        ]);
    }

    /**
     * Normalise the raw `yer` value. The DB stores 'road' / 'garage'.
     * Anything else returns null so the column stays NULL instead of
     * silently accepting a bogus string.
     */
    protected function normalizeLocation(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, Location::values(), true) ? $value : null;
    }

    /**
     * Normalise the complaint type to a valid enum value, or NULL.
     */
    protected function normalizeComplaintType(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ComplaintType::values(), true) ? $value : null;
    }

    /**
     * Normalise the status to a valid enum value. Defaults to Pending.
     */
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

    /**
     * Parse a date cell into a Y-m-d string.
     *
     * Handles:
     *   - d.m.Y (01.06.2026 → 2026-06-01)
     *   - Y-m-d (2026-06-01)
     *   - d/m/Y, d-m-Y
     *   - DateTimeInterface instances
     *   - Excel serial numbers
     *
     * Returns null when the value cannot be interpreted as a date.
     */
    protected function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        // Excel serial number (1900-01-01 = 1, 2026-06-01 ≈ 46174).
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

        // Explicit day-first formats: d.m.Y, d/m/Y, d-m-Y.
        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $value, $m)) {
            try {
                return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->toDateString();
            } catch (\Throwable $e) {
                // fall through to generic parse
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse a time cell into a H:i string.
     *
     * Handles "14:30", "14:30:00", and DateTimeInterface instances.
     */
    protected function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('H:i');
        }

        $value = trim((string) $value);

        // Drop a trailing seconds component if present.
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return null;
    }

    /**
     * Validation rules for the import file.
     *
     * @return array<string, mixed>
     */
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
