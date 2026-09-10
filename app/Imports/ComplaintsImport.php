<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Warehouse;
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
        $rowArray = $row->toArray();
        $rowIndex = $row->getIndex();

        $busDqn = trim((string) ($rowArray['bus_dqn'] ?? $rowArray['dqn'] ?? ''));

        if ($busDqn === '') {
            $this->skipped[] = [
                'row'    => $rowIndex,
                'dqn'    => '—',
                'reason' => __('messages.imports.reasons.dqn_missing_in_row'),
            ];
            return;
        }

        $garageId = $this->garageId;
        $companyId = $this->companyId;

        $bus = Bus::withoutGlobalScopes()
            ->where('dqn', $busDqn)
            ->when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->first();

        if (! $bus) {
            $this->skipped[] = [
                'row'    => $rowIndex,
                'dqn'    => $busDqn,
                'reason' => __('messages.imports.reasons.dqn_not_found'),
            ];
            return;
        }

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

        $stockQuantity = 0;

        if ($partCode !== '' && $usedQuantity > 0) {
            $warehouse = Warehouse::withoutGlobalScopes()
                ->where('code', $partCode)
                ->when($garageId, fn ($q) => $q->where('garage_id', $garageId))
                ->lockForUpdate()
                ->first();

            if (! $warehouse) {
                $this->skipped[] = [
                    'row'    => $rowIndex,
                    'dqn'    => $busDqn,
                    'reason' => __('messages.imports.reasons.part_not_found', ['code' => $partCode]),
                ];
                return;
            }

            if ($usedQuantity > $warehouse->quantity) {
                $this->skipped[] = [
                    'row'    => $rowIndex,
                    'dqn'    => $busDqn,
                    'reason' => __('messages.flash.stock_insufficient', [
                        'name'      => $warehouse->name,
                        'requested' => $usedQuantity,
                        'available' => $warehouse->quantity,
                    ]),
                ];
                return;
            }

            $stockQuantity = $warehouse->quantity;
            $warehouse->decrement('quantity', $usedQuantity);
            $partName ??= $warehouse->name;
        }

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

        if (! empty($rowArray['complaints'])) {
            $complaint->items()->create([
                'description' => $rowArray['complaints'],
                'type'        => $rowArray['complaint_type'] ?? null,
            ]);
        }

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
}