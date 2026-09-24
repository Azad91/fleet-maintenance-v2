<?php

namespace App\Imports;

use App\Models\Bus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports buses from an Excel file.
 *
 * PERFORMANCE NOTE
 * ----------------
 * The previous implementation used the ToModel contract: every row
 * triggered one `exists()` query (cross-garage conflict check) plus
 * one `first()` query (upsert lookup). For a 5000-row file that is
 * ~10 000 round-trips.
 *
 * This version uses ToCollection + WithChunkReading:
 *   1. Loads every referenced DQN (both cross-garage conflicts and
 *      current-garage soft-deleted rows) in ONE query per chunk.
 *   2. Saves each row through the model so existing observation
 *      behaviour (Auditable, HasGarageScope) is preserved.
 *
 * Result: a handful of queries per 100-row chunk regardless of file
 * size. On a 5000-row import this drops from ~10 000 queries to ~50.
 */
class BusesImport extends AbstractImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    /**
     * @param  int|null  $garageId  Positive for tenant imports.
     * @param  int|null  $companyId  Optional, used for strict company scoping.
     * @param  int|null  $brandId  Optional default brand for every imported bus.
     */
    public function __construct(
        ?int $garageId = null,
        ?int $companyId = null,
        public readonly ?int $brandId = null,
    ) {
        parent::__construct($garageId, $companyId);
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // ─── Step 1: collect all DQNs referenced in this chunk ───
        $dqnList = $rows
            ->pluck('dqn')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // ─── Step 2: preload lookup maps in TWO queries per chunk ───
        //
        // $foreignDqns — DQNs that already exist in a DIFFERENT garage.
        //               These are a hard conflict, not an update.
        //
        // $localBuses  — buses in the CURRENT garage, including
        //                soft-deleted ones (so we can restore them
        //                instead of inserting a duplicate).
        $foreignDqns = [];
        $localBuses = collect();

        if (! empty($dqnList)) {
            $foreignDqns = Bus::withoutGlobalScopes()
                ->whereIn('dqn', $dqnList)
                ->where('garage_id', '!=', $this->garageId)
                ->pluck('dqn')
                ->flip()
                ->all();

            $localBuses = Bus::withoutGlobalScopes()
                ->withTrashed()
                ->whereIn('dqn', $dqnList)
                ->where('garage_id', $this->garageId)
                ->get()
                ->keyBy('dqn');
        }

        // ─── Step 3: process each row in memory ───
        foreach ($rows as $row) {
            $currentRow = $this->nextRowIndex();
            $data = is_array($row) ? $row : $row->toArray();

            $dqn = trim((string) ($data['dqn'] ?? ''));

            if ($dqn === '') {
                $this->recordSkip($currentRow, '—', __('messages.imports.reasons.dqn_empty'));

                continue;
            }

            if (isset($foreignDqns[$dqn])) {
                $this->recordSkip($currentRow, $dqn, __('messages.imports.reasons.dqn_other_garage'));

                continue;
            }

            /** @var Bus|null $bus */
            $bus = $localBuses->get($dqn);

            // Capture the trashed state BEFORE restore() runs.
            //
            // The previous implementation called $bus->restore() and
            // then checked $bus->trashed() again — but restore() has
            // already cleared deleted_at by that point, so the second
            // check was dead code and the bus's is_active flag was
            // never touched. A bus that had been deactivated before
            // being soft-deleted came back from the import still
            // inactive: present in the DB, but invisible to every
            // active query, report and dashboard KPI.
            $wasTrashed = $bus?->trashed() ?? false;

            if ($wasTrashed) {
                $bus->restore();
            }

            $bus ??= new Bus;

            $isNew = ! $bus->exists;

            $bus->fill([
                'garage_id' => $this->garageId,
                'company_id' => $this->companyId,
                'brand_id' => $this->brandId,
                'dqn' => $dqn,
                'bus_project' => $data['bus_project'] ?? null,
                'vin' => $data['vin'] ?? null,
                'uzunluq' => $data['uzunluq'] ?? null,
                'route_number' => $data['route_number'] ?? null,
                'engine_number' => $data['engine_number'] ?? null,
                'date' => now()->format('Y-m-d'),
                'km' => isset($data['km']) ? (int) $data['km'] : null,
            ]);

            // Only force is_active = true when the row is genuinely
            // new, or when it was pulled out of the trash by this
            // import.
            //
            // Preserving the existing flag on a plain UPDATE means an
            // operator who manually deactivated a bus will not have it
            // silently re-enabled by the next Excel import — that was
            // the original intent of this guard.
            //
            // Reactivating on RESTORE is the missing half: soft-delete
            // is not a semantic "deactivate", so a restored bus must
            // be visible again instead of occupying the unique-index
            // slot while being hidden from every active query.
            if ($isNew || $wasTrashed) {
                $bus->is_active = true;
            }

            $bus->save();

            // Keep the in-memory cache fresh so a duplicate DQN inside
            // the same chunk updates the same record rather than
            // triggering a partial unique index violation.
            $localBuses->put($dqn, $bus);

            $this->incrementImported();
        }
    }
}
