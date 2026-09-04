<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    public function __construct(
        protected ComplaintStockService $stockService,
        protected ComplaintItemService $itemService
    ) {}

    public function create(array $data, array $detallar = null, array $shikayet = []): Complaint
    {
        if (($data['yer'] ?? null) === 'yol' && !empty($data['driver_id'])) {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['driver_name'] = $driver->full_name;  // əvvəl: surucu_adi
        } else {
            $data['driver_id'] = null;
            $data['driver_name'] = null;
        }

        if (!empty($shikayet) && is_array($shikayet)) {
            $data['shikayet'] = implode("\n", array_filter($shikayet));
        }

        $data['created_by'] = auth()->id();

        $complaint = DB::transaction(function () use ($data, $detallar, $shikayet) {
            if (!empty($detallar) && is_array($detallar)) {
                $data['detallar'] = $this->stockService->deductStock($detallar);
            } else {
                $data['detallar'] = null;
            }

            $complaint = Complaint::create($data);
            $this->itemService->syncItems($complaint, $shikayet, $data['complaint_type'] ?? null); // əvvəl: sikayet_tipi

            return $complaint;
        });

        return $complaint;
    }

    public function update(Complaint $complaint, array $data, array $detallar = null, array $shikayet = []): Complaint
    {
        if (($data['yer'] ?? null) === 'yol' && !empty($data['driver_id'])) {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['driver_name'] = $driver->full_name;
        } else {
            $data['driver_id'] = null;
            $data['driver_name'] = null;
        }

        if (!empty($shikayet) && is_array($shikayet)) {
            $data['shikayet'] = implode("\n", array_filter($shikayet));
        }

        return DB::transaction(function () use ($complaint, $data, $detallar, $shikayet) {
            if (!empty($complaint->detallar)) {
                $this->stockService->restoreStock($complaint->detallar);
            }

            if (!empty($detallar) && is_array($detallar)) {
                $data['detallar'] = $this->stockService->deductStock($detallar);
            } else {
                $data['detallar'] = null;
            }

            $complaint->update($data);
            $this->itemService->syncItems($complaint, $shikayet, $data['complaint_type'] ?? null);

            return $complaint;
        });
    }

    public function close(Complaint $complaint, array $data): Complaint
    {
        $complaint->update([
            'status' => 'həll olundu',
            'end_date' => $data['end_date'],      // əvvəl: is_bitme_tarix
            'end_time' => $data['end_time'],      // əvvəl: is_bitme_saat
            'work_done_by' => $data['work_done'], // əvvəl: gorulen_is
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return $complaint;
    }

    public function delete(Complaint $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            if (!empty($complaint->detallar)) {
                $this->stockService->restoreStock($complaint->detallar);
            }
            $complaint->delete();
        });
    }
}
