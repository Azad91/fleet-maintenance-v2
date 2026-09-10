<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;
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

    /**
     * Manual olaraq atlanan sətirlər (business logic səbəbindən).
     *
     * @var array<int, array{row: int, dqn: string, reason: string}>
     */
    public array $skipped = [];

    /**
     * Uğurla idxal olunan sətir sayı.
     */
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
                'reason' => 'Sətirdə DQN göstərilməyib',
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
                'reason' => 'Bu DQN cari qarajın avtobus siyahısında yoxdur',
            ];
            return;
        }

        // ==================== DETAIL / STOCK ====================
        $partCode = trim((string) (
            $rowArray['part_code']
            ?? $rowArray['code']
            ?? $rowArray['detal_kodu']
            ?? $rowArray['kodu']
            ?? ''
        ));

        $usedQuantity = (int) (
            $rowArray['used_quantity']
            ?? $rowArray['quantity']
            ?? $rowArray['islenen_miqdar']
            ?? $rowArray['miqdar']
            ?? 0
        );

        $partName = $rowArray['part_name']
            ?? $rowArray['name']
            ?? $rowArray['detal_adi']
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
                    'reason' => "Detal ({$partCode}) anbarda tapılmadı",
                ];
                return;
            }

            if ($usedQuantity > $warehouse->quantity) {
                $this->skipped[] = [
                    'row'    => $rowIndex,
                    'dqn'    => $busDqn,
                    'reason' => "Anbarda kifayət qədər '{$warehouse->name}' yoxdur (tələb: {$usedQuantity}, mövcud: {$warehouse->quantity})",
                ];
                return;
            }

            $stockQuantity = $warehouse->quantity;
            $warehouse->decrement('quantity', $usedQuantity);
            $partName ??= $warehouse->name;
        }

        // ==================== COMPLAINT ====================
        $complaint = Complaint::create([
            'garage_id'        => $garageId ?? $bus->garage_id,
            'company_id'       => $companyId ?? $bus->company_id,
            'bus_id'           => $bus->id,
            'yer'              => $rowArray['yer'] ?? null,
            'driver_name'      => $rowArray['driver_name'] ?? $rowArray['surucu_adi'] ?? null,
            'complaint_type'   => $rowArray['complaint_type'] ?? $rowArray['sikayet_tipi'] ?? null,
            'reported_date'    => $rowArray['reported_date'] ?? $rowArray['bildirilme_tarix'] ?? null,
            'reported_time'    => $rowArray['reported_time'] ?? $rowArray['bildirilme_saat'] ?? null,
            'start_date'       => $rowArray['start_date'] ?? $rowArray['is_baslama_tarix'] ?? null,
            'start_time'       => $rowArray['start_time'] ?? $rowArray['is_baslama_saat'] ?? null,
            'end_date'         => $rowArray['end_date'] ?? $rowArray['is_bitme_tarix'] ?? null,
            'end_time'         => $rowArray['end_time'] ?? $rowArray['is_bitme_saat'] ?? null,
            'status'           => $rowArray['status'] ?? 'gözləmədə',
            'km'               => isset($rowArray['km']) ? (int) $rowArray['km'] : null,
            'work_done_by'     => $rowArray['work_done_by'] ?? $rowArray['kim_is_gorub'] ?? null,
            'notes'            => $rowArray['notes'] ?? $rowArray['shikayet'] ?? $rowArray['qeyd'] ?? null,
        ]);

        if (! empty($rowArray['shikayet'])) {
            $complaint->items()->create([
                'description' => $rowArray['shikayet'],
                'type'        => $rowArray['complaint_type'] ?? $rowArray['sikayet_tipi'] ?? null,
            ]);
        }

        if ($partCode !== '' && $usedQuantity > 0) {
            $complaint->details()->create([
                'shikayet_index' => 0,
                'code'           => $partCode,
                'name'           => $partName ?? $partCode,
                'stock_quantity' => $stockQuantity,
                'used_quantity'  => $usedQuantity,
                'notes'          => $rowArray['detail_notes'] ?? $rowArray['notes'] ?? $rowArray['qeyd'] ?? null,
            ]);
        }

        $this->importedCount++;
    }

    public function rules(): array
    {
        return [
            'bus_dqn'        => 'sometimes|nullable',
            'dqn'            => 'sometimes|nullable',
            'status'         => 'nullable|in:gözləmədə,işdə,həll olundu',
            'yer'            => 'nullable|in:yol,qaraj',
            'complaint_type' => 'nullable|string',
            'sikayet_tipi'   => 'nullable|string',
            'km'             => 'nullable|integer|min:0',
        ];
    }

    /**
     * Validation xətaları üçün dostcasına mesajlar.
     */
    public function customValidationMessages(): array
    {
        return [
            'status.in'    => 'Status yalnız "gözləmədə", "işdə" və ya "həll olundu" ola bilər.',
            'yer.in'       => 'Yer yalnız "yol" və ya "qaraj" ola bilər.',
            'km.integer'   => 'KM tam ədəd olmalıdır.',
            'km.min'       => 'KM mənfi ola bilməz.',
        ];
    }
}
