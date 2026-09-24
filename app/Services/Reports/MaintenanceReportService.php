<?php

namespace App\Services\Reports;

use App\Enums\ComplaintType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceReportService
{
    public function summary(ReportPeriod $period, ReportScope $scope): array
    {
        $complaintIds = $this->complaintIds($period, $scope);

        $stats = DB::table('complaints')
            ->whereIn('id', $complaintIds)
            ->select(
                DB::raw('COUNT(*) as opened'),
                DB::raw('SUM(CASE WHEN closed_at IS NOT NULL THEN 1 ELSE 0 END) as closed'),
                DB::raw("SUM(CASE WHEN complaint_type = 'maintenance' AND service_km IS NOT NULL THEN 1 ELSE 0 END) as motor_oil_count")
            )
            ->first();

        $partsAgg = $this->partAggregates($complaintIds);

        $closedRows = DB::table('complaints')
            ->whereIn('id', $complaintIds)
            ->whereNotNull('closed_at')
            ->get(['created_at', 'closed_at']);

        $avgHours = $closedRows->isEmpty()
            ? null
            : round($closedRows->avg(fn ($row) => (strtotime($row->closed_at) - strtotime($row->created_at)) / 3600), 1);

        $byType = DB::table('complaints')
            ->whereIn('id', $complaintIds)
            ->whereNotNull('complaint_type')
            ->select('complaint_type', DB::raw('COUNT(*) as total'))
            ->groupBy('complaint_type')
            ->pluck('total', 'complaint_type')
            ->all();

        $byLocation = DB::table('complaints')
            ->whereIn('id', $complaintIds)
            ->whereNotNull('yer')
            ->select('yer', DB::raw('COUNT(*) as total'))
            ->groupBy('yer')
            ->pluck('total', 'yer')
            ->all();

        $topBusRow = DB::table('complaints')
            ->whereIn('id', $complaintIds)
            ->select('bus_id', DB::raw('COUNT(*) as total'))
            ->groupBy('bus_id')
            ->orderByDesc('total')
            ->first();

        $topBus = null;
        if ($topBusRow) {
            $bus = DB::table('buses')
                ->where('id', $topBusRow->bus_id)
                ->whereNull('deleted_at')
                ->first(['id', 'dqn', 'route_number', 'bus_project']);
            if ($bus) {
                $bus->card_count = (int) $topBusRow->total;
                $topBus = $bus;
            }
        }

        return [
            'opened' => (int) ($stats->opened ?? 0),
            'closed' => (int) ($stats->closed ?? 0),
            'parts_lines' => (int) $partsAgg->line_count,
            'distinct_parts' => (int) $partsAgg->distinct_parts,
            'total_quantity' => (int) $partsAgg->total_quantity,
            'total_cost' => (float) $partsAgg->total_cost,
            'motor_oil' => (int) ($stats->motor_oil_count ?? 0),
            'avg_close_hours' => $avgHours,
            'by_type' => $byType,
            'by_location' => $byLocation,
            'top_bus' => $topBus,
        ];
    }

    public function perBus(ReportPeriod $period, ReportScope $scope): Collection
    {
        $complaintIds = $this->complaintIds($period, $scope);

        if (empty($complaintIds)) {
            return collect();
        }

        $complaintStats = DB::table('complaints')
            ->whereIn('id', $complaintIds)
            ->select(
                'bus_id',
                DB::raw('COUNT(*) as cards_opened'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as cards_closed"),
                DB::raw("SUM(CASE WHEN complaint_type = 'maintenance' AND service_km IS NOT NULL THEN 1 ELSE 0 END) as motor_oil_count")
            )
            ->groupBy('bus_id')
            ->get()
            ->keyBy('bus_id');

        $partStats = DB::table('complaint_details')
            ->whereIn('complaint_id', $complaintIds)
            ->whereNull('deleted_at')
            ->where('used_quantity', '>', 0)
            ->select(
                'complaint_id',
                DB::raw('SUM(used_quantity) as total_qty'),
                DB::raw('SUM(used_quantity * COALESCE(price_at_use, 0)) as total_cost')
            )
            ->groupBy('complaint_id')
            ->get();

        // Roll up per bus via complaint → bus_id
        $complaintToBus = DB::table('complaints')
            ->whereIn('id', $complaintIds)
            ->pluck('bus_id', 'id');

        $partStatsPerBus = [];
        foreach ($partStats as $p) {
            $busId = $complaintToBus[$p->complaint_id] ?? null;
            if (! $busId) {
                continue;
            }
            if (! isset($partStatsPerBus[$busId])) {
                $partStatsPerBus[$busId] = ['total_qty' => 0, 'total_cost' => 0];
            }
            $partStatsPerBus[$busId]['total_qty'] += (int) $p->total_qty;
            $partStatsPerBus[$busId]['total_cost'] += (float) $p->total_cost;
        }

        $busIds = $complaintStats->keys()->all();
        $buses = DB::table('buses')
            ->whereIn('id', $busIds)
            ->whereNull('deleted_at')
            ->get(['id', 'dqn', 'route_number', 'bus_project', 'brand_id', 'garage_id'])
            ->keyBy('id');

        return collect($busIds)
            ->map(function ($busId) use ($complaintStats, $partStatsPerBus, $buses) {
                $bus = $buses->get($busId);
                if (! $bus) {
                    return null;
                }
                $c = $complaintStats->get($busId);
                $p = $partStatsPerBus[$busId] ?? ['total_qty' => 0, 'total_cost' => 0];

                return (object) [
                    'bus' => $bus,
                    'cards_opened' => (int) ($c->cards_opened ?? 0),
                    'cards_closed' => (int) ($c->cards_closed ?? 0),
                    'motor_oil_count' => (int) ($c->motor_oil_count ?? 0),
                    'total_qty' => $p['total_qty'],
                    'total_cost' => $p['total_cost'],
                ];
            })
            ->filter()
            ->sortByDesc('cards_opened')
            ->values();
    }

    public function perPart(ReportPeriod $period, ReportScope $scope): Collection
    {
        $complaintIds = $this->complaintIds($period, $scope);

        if (empty($complaintIds)) {
            return collect();
        }

        return DB::table('complaint_details')
            ->whereIn('complaint_id', $complaintIds)
            ->whereNull('deleted_at')
            ->where('used_quantity', '>', 0)
            ->select(
                'code',
                DB::raw('MAX(name) as name'),
                DB::raw('COUNT(DISTINCT complaint_id) as times_used'),
                DB::raw('SUM(used_quantity) as total_qty'),
                DB::raw('SUM(used_quantity * COALESCE(price_at_use, 0)) as total_cost'),
                DB::raw("SUM(CASE WHEN source_type = 'warehouse' THEN used_quantity ELSE 0 END) as qty_warehouse"),
                DB::raw("SUM(CASE WHEN source_type = 'service_vehicle' THEN used_quantity ELSE 0 END) as qty_service_vehicle"),
                DB::raw("SUM(CASE WHEN source_type = 'historical' THEN used_quantity ELSE 0 END) as qty_historical")
            )
            ->groupBy('code')
            ->orderByDesc('total_cost')
            ->limit(100)
            ->get();
    }

    public function motorOil(ReportPeriod $period, ReportScope $scope): Collection
    {
        $complaints = DB::table('complaints')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereBetween('created_at', [$period->from, $period->to])
            ->whereNull('deleted_at')
            ->where('complaint_type', ComplaintType::Maintenance->value)
            ->whereNotNull('service_km')
            ->when($scope->brandId, fn ($q) => $q->whereIn('bus_id', function ($sub) use ($scope) {
                $sub->select('id')->from('buses')->where('brand_id', $scope->brandId);
            }))
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        if ($complaints->isEmpty()) {
            return collect();
        }

        $busIds = $complaints->pluck('bus_id')->unique()->all();
        $complaintIds = $complaints->pluck('id')->all();

        $buses = DB::table('buses')
            ->whereIn('id', $busIds)
            ->get(['id', 'dqn', 'route_number', 'bus_project'])
            ->keyBy('id');

        $details = DB::table('complaint_details')
            ->whereIn('complaint_id', $complaintIds)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('complaint_id');

        return $complaints->map(function ($complaint) use ($buses, $details) {
            $lines = collect($details->get($complaint->id, []));

            return (object) [
                'complaint' => $complaint,
                'bus' => $buses->get($complaint->bus_id),
                'details' => $lines,
                'parts_count' => $lines->count(),
                'total_qty' => (int) $lines->sum('used_quantity'),
                'total_cost' => (float) $lines->sum(fn ($d) => (float) $d->used_quantity * (float) ($d->price_at_use ?? 0)),
            ];
        });
    }

    public function mostRepaired(ReportPeriod $period, ReportScope $scope): Collection
    {
        $complaintIds = $this->complaintIds($period, $scope);

        if (empty($complaintIds)) {
            return collect();
        }

        $rows = DB::table('complaints')
            ->whereIn('id', $complaintIds)
            ->select(
                'bus_id',
                DB::raw('COUNT(*) as cards_count'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count"),
                DB::raw('SUM(CASE WHEN closed_at IS NOT NULL THEN EXTRACT(EPOCH FROM (closed_at - created_at)) / 3600 ELSE 0 END) as total_hours')
            )
            ->groupBy('bus_id')
            ->orderByDesc('cards_count')
            ->limit(50)
            ->get();

        $busIds = $rows->pluck('bus_id')->all();

        $buses = DB::table('buses')
            ->whereIn('id', $busIds)
            ->get(['id', 'dqn', 'route_number', 'bus_project'])
            ->keyBy('id');

        $costByBus = DB::table('complaint_details')
            ->whereIn('complaint_id', $complaintIds)
            ->whereNull('deleted_at')
            ->where('used_quantity', '>', 0)
            ->select(
                'complaint_id',
                DB::raw('SUM(used_quantity * COALESCE(price_at_use, 0)) as total_cost')
            )
            ->groupBy('complaint_id')
            ->get();

        $complaintToBus = DB::table('complaints')
            ->whereIn('id', $complaintIds)
            ->pluck('bus_id', 'id');

        $costRollup = [];
        foreach ($costByBus as $row) {
            $busId = $complaintToBus[$row->complaint_id] ?? null;
            if (! $busId) {
                continue;
            }
            $costRollup[$busId] = ($costRollup[$busId] ?? 0) + (float) $row->total_cost;
        }

        return $rows->map(function ($row) use ($buses, $costRollup) {
            return (object) [
                'bus' => $buses->get($row->bus_id),
                'cards_count' => (int) $row->cards_count,
                'completed_count' => (int) $row->completed_count,
                'total_hours' => round((float) $row->total_hours, 1),
                'total_cost' => (float) ($costRollup[$row->bus_id] ?? 0),
            ];
        });
    }

    private function complaintIds(ReportPeriod $period, ReportScope $scope): array
    {
        return DB::table('complaints')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereBetween('created_at', [$period->from, $period->to])
            ->whereNull('deleted_at')
            ->when($scope->brandId, fn ($q) => $q->whereIn('bus_id', function ($sub) use ($scope) {
                $sub->select('id')->from('buses')->where('brand_id', $scope->brandId);
            }))
            ->pluck('id')
            ->all();
    }

    private function partAggregates(array $complaintIds): object
    {
        if (empty($complaintIds)) {
            return (object) [
                'line_count' => 0,
                'distinct_parts' => 0,
                'total_quantity' => 0,
                'total_cost' => 0,
            ];
        }

        $row = DB::table('complaint_details')
            ->whereIn('complaint_id', $complaintIds)
            ->whereNull('deleted_at')
            ->where('used_quantity', '>', 0)
            ->select(
                DB::raw('COUNT(*) as line_count'),
                DB::raw('COUNT(DISTINCT code) as distinct_parts'),
                DB::raw('SUM(used_quantity) as total_quantity'),
                DB::raw('SUM(used_quantity * COALESCE(price_at_use, 0)) as total_cost')
            )
            ->first();

        return $row ?: (object) [
            'line_count' => 0,
            'distinct_parts' => 0,
            'total_quantity' => 0,
            'total_cost' => 0,
        ];
    }
}
