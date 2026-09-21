<?php

namespace App\Services;

use App\Models\Bus;
use Illuminate\Pagination\LengthAwarePaginator;

class BusService
{
    public function getPaginatedBuses(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Bus::with([
            'brand',
            'latestKmRecord',
            'dailyKmRecords' => fn ($q) => $q->limit(2),
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('dqn', 'ILIKE', "%{$search}%")
                    ->orWhere('route_number', 'ILIKE', "%{$search}%")
                    ->orWhere('bus_project', 'ILIKE', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function advancedSearch(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Bus::with([
            'brand',
            'latestKmRecord',
            'dailyKmRecords' => fn ($q) => $q->limit(2),
        ]);

        // Brand filter uses an exact match because it is a foreign key.
        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        $searchableFields = [
            'bus_project', 'vin', 'uzunluq', 'route_number', 'dqn', 'engine_number',
        ];

        foreach ($searchableFields as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, 'ILIKE', '%'.$filters[$field].'%');
            }
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function createBus(array $data): Bus
    {
        $data['date'] = now()->format('Y-m-d');

        return Bus::create($data);
    }

    public function updateBus(Bus $bus, array $data): Bus
    {
        $bus->update($data);

        return $bus->fresh();
    }

    public function deleteBus(Bus $bus): void
    {
        $bus->delete();
    }

    /**
     * Bulk-update the active flag on multiple buses.
     *
     * IMPORTANT: the audit call MUST run BEFORE the actual DB update.
     * `auditBulkUpdate()` compares the requested new value against the
     * current value stored in the database. If we update first, the
     * comparison sees identical values and silently skips logging.
     *
     * Ids are processed in chunks so a 10 000-row selection does not
     * overflow PostgreSQL's parameter limit (65 535) nor time out
     * inside auditBulkUpdate's per-row INSERT loop.
     */
    public function bulkUpdateStatus(array $ids, bool $isActive): void
    {
        if (empty($ids)) {
            return;
        }

        $event = $isActive ? 'bulk_activated' : 'bulk_deactivated';

        foreach (array_chunk($ids, 500) as $chunk) {
            Bus::auditBulkUpdate($chunk, ['is_active' => $isActive], $event);

            Bus::whereIn('id', $chunk)->update(['is_active' => $isActive]);
        }
    }

    /**
     * Bulk soft-delete multiple buses.
     *
     * The audit snapshot must be taken before deletion so that the
     * original values are preserved in the audit log. Ids are
     * chunked for the same reason as bulkUpdateStatus().
     */
    public function bulkDelete(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            Bus::auditBulkDelete($chunk);

            Bus::whereIn('id', $chunk)->delete();
        }
    }

    /**
     * Bulk soft-delete ALL buses matching the given filters.
     *
     * Ids are streamed from the database in chunks via cursor() so the
     * full list never lives in PHP memory. Each chunk is then deleted
     * through bulkDelete() so audit entries continue to be written.
     */
    public function bulkDeleteAllByFilters(array $filters): int
    {
        $query = Bus::query();

        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        $searchableFields = [
            'bus_project', 'vin', 'uzunluq', 'route_number', 'dqn', 'engine_number',
        ];

        foreach ($searchableFields as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, 'ILIKE', '%'.$filters[$field].'%');
            }
        }

        $totalDeleted = 0;
        $buffer       = [];

        foreach ($query->select('id')->cursor() as $row) {
            $buffer[] = $row->id;

            if (count($buffer) >= 500) {
                $this->bulkDelete($buffer);
                $totalDeleted += count($buffer);
                $buffer = [];
            }
        }

        if (! empty($buffer)) {
            $this->bulkDelete($buffer);
            $totalDeleted += count($buffer);
        }

        return $totalDeleted;
    }
}
