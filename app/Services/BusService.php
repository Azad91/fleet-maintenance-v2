<?php

namespace App\Services;

use App\Models\Bus;
use Illuminate\Pagination\LengthAwarePaginator;

class BusService
{
    public function getPaginatedBuses(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Bus::with('latestKmRecord');

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
        $query = Bus::with('latestKmRecord');

        $fields = ['bus_project', 'vin', 'uzunluq', 'route_number', 'dqn', 'engine_number'];
        foreach ($fields as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, 'ILIKE', "%{$filters[$field]}%");
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

    public function bulkUpdateStatus(array $ids, bool $isActive): void
    {
        Bus::whereIn('id', $ids)->update(['is_active' => $isActive]);
        Bus::auditBulkUpdate($ids, ['is_active' => $isActive], $isActive ? 'bulk_activated' : 'bulk_deactivated');
    }

    public function bulkDelete(array $ids): void
    {
        Bus::auditBulkDelete($ids);
        Bus::whereIn('id', $ids)->delete();
    }
}
