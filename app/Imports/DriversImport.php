<?php

namespace App\Imports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DriversImport extends AbstractImport implements SkipsEmptyRows, ToModel, WithChunkReading, WithHeadingRow
{
    public function model(array $row)
    {
        $currentRow = $this->nextRowIndex();

        $code = trim((string) ($row['code'] ?? ''));
        $firstName = trim((string) ($row['first_name'] ?? ''));

        if (empty($code) || empty($firstName)) {
            $this->recordSkip(
                $currentRow,
                $code ?: '—',
                empty($code)
                    ? __('messages.imports.reasons.driver_code_empty')
                    : __('messages.imports.reasons.first_name_empty')
            );

            return null;
        }

        // ────────────────────────────────────────────────────────
        // SOFT-DELETE HANDLING
        // ────────────────────────────────────────────────────────
        // We deliberately search WITHOUT global scopes so that a
        // soft-deleted driver with the same (garage_id, code) is
        // found — otherwise updateOrCreate would silently create a
        // duplicate, and the partial unique index would only permit
        // it because the historical row is hidden.
        //
        // However, `withoutGlobalScopes()` also removes the
        // SoftDeletingScope, so the found row is returned in its
        // trashed state. A plain ->update() would write the new
        // attributes but leave `deleted_at` untouched — the driver
        // would be marked is_active=true yet remain invisible to
        // every active query.
        //
        // Restoring before fill+save clears deleted_at as part of
        // the same write, so the imported driver is fully live
        // again.
        // ────────────────────────────────────────────────────────
        $driver = Driver::withoutGlobalScopes()
            ->where('code', $code)
            ->where('garage_id', $this->garageId)
            ->first();

        if ($driver === null) {
            $driver = new Driver;
            $driver->code = $code;
            $driver->garage_id = $this->garageId;
        } elseif ($driver->trashed()) {
            $driver->restore();
        }

        $driver->fill([
            'company_id' => $this->companyId,
            'first_name' => $firstName,
            'last_name' => $row['last_name'] ?? null,
            'phone' => $row['phone'] ?? null,
            'position' => $row['position'] ?? null,
            'is_active' => true,
            'notes' => $row['notes'] ?? null,
        ]);

        $driver->save();

        $this->incrementImported();

        return $driver;
    }
}
