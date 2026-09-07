<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Row;
use Illuminate\Validation\ValidationException;

class ComplaintsImport implements OnEachRow, WithHeadingRow, WithValidation, ShouldQueue, WithChunkReading
{
    public function __construct(
        public int $garageId,
        public ?int $companyId = null
    ) {}

    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow(Row $row)
    {
        $rowArray = $row->toArray();

        $busDqn = trim((string) ($rowArray['bus_dqn'] ?? $rowArray['dqn'] ?? ''));
        if (empty($busDqn)) {
            return;
        }

        $garageId = $this->garageId;
        $companyId = $this->companyId;

        $bus = Bus::withoutGlobalScopes()
            ->where('dqn', $busDqn)
            ->when($garageId, fn($q) => $q->where('garage_id', $garageId))
            ->first();

        if (!$bus) {
            return;
        }

        // ✅ DƏYİŞİKLİK: DB::transaction LƏĞV EDİLDİ – WithChunkReading özü idarə edir
        $partCode = trim((string) ($rowArray['part_code'] ?? $rowArray['code'] ?? $rowArray['detal_kodu'] ?? $rowArray['kodu'] ?? ''));
        $usedQuantity = (int) ($rowArray['used_quantity'] ?? $rowArray['quantity'] ?? $rowArray['islenen_miqdar'] ?? $rowArray['miqdar'] ?? 0);
        $partName = $rowArray['part_name'] ?? $rowArray['name'] ?? $rowArray['detal_adi'] ?? null;
        $stockQuantity = 0;

        if (!empty($partCode) && $usedQuantity > 0) {
            $warehouse = Warehouse::withoutGlobalScopes()
                ->where('code', $partCode)
                ->when($garageId, fn($q) => $q->where('garage_id', $garageId))
                ->lockForUpdate()
                ->first();

            if (!$warehouse) {
                throw ValidationException::withMessages(['part_code' => "Detal ({$partCode}) cari qarajın anbarında tapılmadı."]);
            }

            if ($usedQuantity > $warehouse->quantity) {
                throw ValidationException::withMessages(['used_quantity' => "Anbarda kifayət qədər '{$warehouse->name}' yoxdur."]);
            }

            $warehouse->decrement('quantity', $usedQuantity);
            $stockQuantity = $warehouse->quantity;
            $partName ??= $warehouse->name;
        }

        $complaint = Complaint::create([
            'garage_id' => $garageId ?? $bus->garage_id,
            'company_id' => $companyId ?? $bus->company_id,
            'bus_id' => $bus->id,
            'yer' => $rowArray['yer'] ?? null,
            'driver_name' => $rowArray['driver_name'] ?? $rowArray['surucu_adi'] ?? null,
            'complaint_type' => $rowArray['complaint_type'] ?? $rowArray['sikayet_tipi'] ?? null,
            'reported_date' => $rowArray['reported_date'] ?? $rowArray['bildirilme_tarix'] ?? null,
            'reported_time' => $rowArray['reported_time'] ?? $rowArray['bildirilme_saat'] ?? null,
            'start_date' => $rowArray['start_date'] ?? $rowArray['is_baslama_tarix'] ?? null,
            'start_time' => $rowArray['start_time'] ?? $rowArray['is_baslama_saat'] ?? null,
            'end_date' => $rowArray['end_date'] ?? $rowArray['is_bitme_tarix'] ?? null,
            'end_time' => $rowArray['end_time'] ?? $rowArray['is_bitme_saat'] ?? null,
            'status' => $rowArray['status'] ?? 'gözləmədə',
            'km' => isset($rowArray['km']) ? (int) $rowArray['km'] : null,
            'work_done_by' => $rowArray['work_done_by'] ?? $rowArray['kim_is_gorub'] ?? null,
            'notes' => $rowArray['notes'] ?? $rowArray['shikayet'] ?? $rowArray['qeyd'] ?? null,
        ]);

        if (!empty($rowArray['shikayet'])) {
            $complaint->items()->create([
                'description' => $rowArray['shikayet'],
                'type' => $rowArray['complaint_type'] ?? $rowArray['sikayet_tipi'] ?? null,
            ]);
        }

        if (!empty($partCode) && $usedQuantity > 0) {
            $complaint->details()->create([
                'shikayet_index' => 0,
                'code' => $partCode,
                'name' => $partName ?? $partCode,
                'stock_quantity' => $stockQuantity,
                'used_quantity' => $usedQuantity,
                'notes' => $rowArray['detail_notes'] ?? $rowArray['notes'] ?? $rowArray['qeyd'] ?? null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'bus_dqn' => 'sometimes|nullable',
            'dqn' => 'sometimes|nullable',
            'status' => 'nullable|in:gözləmədə,işdə,həll olundu',
            'yer' => 'nullable|in:yol,qaraj',
            'complaint_type' => 'nullable|string',
            'sikayet_tipi' => 'nullable|string',
            'km' => 'nullable|integer|min:0',
        ];
    }
}
