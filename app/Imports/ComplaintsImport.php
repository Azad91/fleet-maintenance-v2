<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class ComplaintsImport implements OnEachRow, SkipsOnFailure, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public array $skipped = [];
    public int $importedCount = 0;

    public function __construct(
        public int $garageId,
        public ?int $companyId = null
    ) {}

    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow(Row $row): void
    {
        $this->processRow($row->toArray(), $row->getIndex());
    }

    /**
     * Process a single import row.
     *
     * Extracted from onRow() so that it can be tested directly without
     * constructing a Maatwebsite\Excel\Row instance.
     *
     * All DB mutations (stock deduction, complaint, items, details) run
     * inside a single transaction. Any failure rolls back the entire row,
     * including the stock decrement, so a partial row never persists.
     */
    public function processRow(array $rowArray, int $rowIndex): void
    {
        // ---------- 1. PRE-CHECKS (no DB mutation) ----------

        $busDqn = trim((string) ($rowArray['bus_dqn'] ?? $rowArray['dqn'] ?? ''));

        if ($busDqn === '') {
            $this->recordSkip($rowIndex, '—', __('messages.imports.reasons.dqn_missing_in_row'));
            return;
        }

        $garageId  = $this->garageId;
        $companyId = $this->companyId;

        $bus = Bus::withoutGlobalScopes()
            ->where('dqn', $busDqn)
            ->when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->first();

        if (! $bus) {
            $this->recordSkip($rowIndex, $busDqn, __('messages.imports.reasons.dqn_not_found'));
            return;
        }

        // ---------- 2. EXTRACT VALUES ----------

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

        // ---------- 3. ATOMIC BLOCK ----------
        // Every mutation happens inside this transaction. If any step
        // throws, PostgreSQL rolls back the whole row — no partial state.

        $skipReason = DB::transaction(function () use (
            $rowArray,
            $bus,
            $garageId,
            $companyId,
            $partCode,
            $usedQuantity,
            &$partName
        ) {
            $stockQuantity = 0;

            // 3a. Stock deduction (with row lock held for the duration)
            if ($partCode !== '' && $usedQuantity > 0) {
                $warehouse = Warehouse::withoutGlobalScopes()
                    ->where('code', $partCode)
                    ->when($garageId, fn ($q) => $q->where('garage_id', $garageId))
                    ->lockForUpdate()
                    ->first();

                if (! $warehouse) {
                    return __('messages.imports.reasons.part_not_found', ['code' => $partCode]);
                }

                if ($usedQuantity > $warehouse->quantity) {
                    return __('messages.flash.stock_insufficient', [
                        'name'      => $warehouse->name,
                        'requested' => $usedQuantity,
                        'available' => $warehouse->quantity,
                    ]);
                }

                $stockQuantity = $warehouse->quantity;
                $warehouse->decrement('quantity', $usedQuantity);
                $partName ??= $warehouse->name;
            }

            // 3b. Complaint header
            $complaint = Complaint::create([
                'garage_id'      => $garageId ?? $bus->garage_id,
                'company_id'     => $companyId ?? $bus->company_id,
                'bus_id'         => $bus->id,
                'yer'            => $rowArray['yer'] ?? null,
                'driver_name'    => $rowArray['driver_name'] ?? null,
                'complaint_type' => $rowArray['complaint_type'] ?? null,
                'reported_date'  => $rowArray['reported_date'] ?? null,
                'reported_time'  => $rowArray['reported_time'] ?? null,
                'start_date'     => $rowArray['start_date'] ?? null,
                'start_time'     => $rowArray['start_time'] ?? null,
                'end_date'       => $rowArray['end_date'] ?? null,
                'end_time'       => $rowArray['end_time'] ?? null,
                'status'         => $rowArray['status'] ?? 'pending',
                'km'             => isset($rowArray['km']) ? (int) $rowArray['km'] : null,
                'work_done_by'   => $rowArray['work_done_by'] ?? null,
                'notes'          => $rowArray['notes'] ?? null,
            ]);

            // 3c. Complaint item(s)
            if (! empty($rowArray['complaints'])) {
                $complaint->items()->create([
                    'description' => $rowArray['complaints'],
                    'type'        => $rowArray['complaint_type'] ?? null,
                ]);
            }

            // 3d. Complaint detail(s)
            if ($partCode !== '' && $usedQuantity > 0) {
                $complaint->details()->create([
                    'shikayet_index' => 0,
                    'code'           => $partCode,
                    'name'           => $partName ?? $partCode,
                    'stock_quantity' => $stockQuantity,
                    'used_quantity'  => $usedQuantity,
                    'notes'          => $rowArray['detail_notes'] ?? $rowArray['notes'] ?? null,
                ]);
            }

            // null = success
            return null;
        });

        // ---------- 4. HANDLE RESULT ----------

        if ($skipReason !== null) {
            $this->recordSkip($rowIndex, $busDqn, $skipReason);
            return;
        }

        $this->importedCount++;
    }

    public function rules(): array
    {
        return [
            'bus_dqn'        => 'sometimes|nullable',
            'dqn'            => 'sometimes|nullable',
            'status'         => 'nullable|in:pending,in_progress,completed',
            'yer'            => 'nullable|in:road,garage',
            'complaint_type' => 'nullable|string',
            'km'             => 'nullable|integer|min:0',
        ];
    }

    /**
     * Record a skipped row. Centralized so future changes (e.g. logging,
     * metrics) happen in one place.
     */
    private function recordSkip(int $rowIndex, string $dqn, string $reason): void
    {
        $this->skipped[] = [
            'row'    => $rowIndex,
            'dqn'    => $dqn,
            'reason' => $reason,
        ];
    }
}
