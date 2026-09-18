<?php

namespace App\Imports;

use App\Models\Bus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BusesImport extends AbstractImport implements ToModel, WithChunkReading, WithHeadingRow
{
    /**
     * @param  int|null  $garageId    Positive for tenant imports.
     * @param  int|null  $companyId   Optional, used for strict company scoping.
     * @param  int|null  $brandId     Optional default brand for every imported bus.
     *                                Selected by the operator on the import form.
     */
    public function __construct(
        ?int $garageId = null,
        ?int $companyId = null,
        public readonly ?int $brandId = null,
    ) {
        parent::__construct($garageId, $companyId);
    }

    public function model(array $row)
    {
        $currentRow = $this->nextRowIndex();

        $dqn = trim((string) ($row['dqn'] ?? ''));

        if ($dqn === '') {
            $this->recordSkip($currentRow, '—', __('messages.imports.reasons.dqn_empty'));

            return null;
        }

        // Reject if the DQN exists in a different garage — that is a
        // hard conflict, not an update.
        $existsInAnotherGarage = Bus::withoutGlobalScopes()
            ->where('dqn', $dqn)
            ->where('garage_id', '!=', $this->garageId)
            ->exists();

        if ($existsInAnotherGarage) {
            $this->recordSkip($currentRow, $dqn, __('messages.imports.reasons.dqn_other_garage'));

            return null;
        }

        $bus = Bus::withoutGlobalScopes()
            ->withTrashed()
            ->where('dqn', $dqn)
            ->where('garage_id', $this->garageId)
            ->first();

        if ($bus?->trashed()) {
            $bus->restore();
        }

        $bus ??= new Bus;

        $bus->fill([
            'garage_id' => $this->garageId,
            'company_id' => $this->companyId,
            'brand_id' => $this->brandId,
            'dqn' => $dqn,
            'bus_project' => $row['bus_project'] ?? null,
            'vin' => $row['vin'] ?? null,
            'uzunluq' => $row['uzunluq'] ?? null,
            'route_number' => $row['route_number'] ?? null,
            'engine_number' => $row['engine_number'] ?? null,
            'date' => now()->format('Y-m-d'),
            'is_active' => true,
            'km' => isset($row['km']) ? (int) $row['km'] : null,
        ]);

        $this->incrementImported();

        return $bus;
    }
}
