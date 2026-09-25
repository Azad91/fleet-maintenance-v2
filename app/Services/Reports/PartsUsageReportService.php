<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregations for the parts-usage report domain (#16–#19).
 *
 * All queries are based on complaint_details — that table is the
 * single source of truth for "which part was consumed on which bus".
 * Historical and inspection rows are excluded where they would
 * distort the numbers.
 */
class PartsUsageReportService
{
    // ==================================================================
    // #16 — PARTS USAGE BY BUS
    // ==================================================================

    /**
     * Total parts consumed per bus during the period.
     *
     * @return Collection<int, object>
     */
    public function perBus(ReportPeriod $period, ReportScope $scope): Collection
    {
        return DB::table('complaint_details as cd')
            ->join('complaints as c', 'c.id', '=', 'cd.complaint_id')
            ->join('buses as b', 'b.id', '=', 'c.bus_id')
            ->whereIn('cd.garage_id', $scope->garageIds)
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->where('b.brand_id', $scope->brandId))
            ->whereIn('cd.source_type', ['warehouse', 'service_vehicle'])
            ->where('cd.used_quantity', '>', 0)
            ->select(
                'b.id as bus_id',
                'b.dqn',
                'b.route_number',
                'b.bus_project',
                DB::raw('COUNT(DISTINCT cd.complaint_id) as cards_count'),
                DB::raw('COUNT(DISTINCT cd.code) as distinct_parts'),
                DB::raw('SUM(cd.used_quantity) as total_quantity'),
                DB::raw('SUM(cd.used_quantity * COALESCE(cd.price_at_use, 0)) as total_cost'),
            )
            ->groupBy('b.id', 'b.dqn', 'b.route_number', 'b.bus_project')
            ->orderByDesc('total_cost')
            ->get();
    }

    // ==================================================================
    // #17 — TOP CONSUMED PARTS
    // ==================================================================

    /**
     * The 20 most-consumed parts (by total quantity) in the period.
     *
     * @return Collection<int, object>
     */
    public function topConsumed(ReportPeriod $period, ReportScope $scope, int $limit = 20): Collection
    {
        return DB::table('complaint_details as cd')
            ->join('complaints as c', 'c.id', '=', 'cd.complaint_id')
            ->whereIn('cd.garage_id', $scope->garageIds)
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->whereIn('c.bus_id', function ($sub) use ($scope) {
                $sub->select('id')->from('buses')->where('brand_id', $scope->brandId);
            }))
            ->where('cd.used_quantity', '>', 0)
            ->select(
                'cd.code',
                DB::raw('MAX(cd.name) as name'),
                DB::raw('COUNT(DISTINCT cd.complaint_id) as times_used'),
                DB::raw('SUM(cd.used_quantity) as total_quantity'),
                DB::raw('SUM(cd.used_quantity * COALESCE(cd.price_at_use, 0)) as total_cost'),
            )
            ->groupBy('cd.code')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    // ==================================================================
    // #18 — BUS MAINTENANCE COST
    // ==================================================================

    /**
     * Monthly cost per bus (sum of parts × price_at_use).
     *
     * Uses the same source table as #16 but the metric is COST, not
     * quantity, so it can be reported independently.
     *
     * @return Collection<int, object>
     */
    public function busCost(ReportPeriod $period, ReportScope $scope): Collection
    {
        return DB::table('complaint_details as cd')
            ->join('complaints as c', 'c.id', '=', 'cd.complaint_id')
            ->join('buses as b', 'b.id', '=', 'c.bus_id')
            ->whereIn('cd.garage_id', $scope->garageIds)
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->where('b.brand_id', $scope->brandId))
            ->whereIn('cd.source_type', ['warehouse', 'service_vehicle'])
            ->select(
                'b.id as bus_id',
                'b.dqn',
                'b.route_number',
                DB::raw('COUNT(DISTINCT c.id) as cards_count'),
                DB::raw('SUM(cd.used_quantity * COALESCE(cd.price_at_use, 0)) as total_cost'),
                DB::raw('AVG(cd.price_at_use) as avg_part_price'),
            )
            ->groupBy('b.id', 'b.dqn', 'b.route_number')
            ->havingRaw('SUM(cd.used_quantity * COALESCE(cd.price_at_use, 0)) > 0')
            ->orderByDesc('total_cost')
            ->get();
    }

    // ==================================================================
    // #19 — PARTS PER COMPLAINT
    // ==================================================================

    /**
     * Average number of distinct parts consumed per complaint.
     *
     * @return array{total_complaints: int, total_parts: int, avg_parts_per_complaint: float}
     */
    public function perComplaint(ReportPeriod $period, ReportScope $scope): array
    {
        $row = DB::table('complaints as c')
            ->leftJoin('complaint_details as cd', function ($join) {
                $join->on('cd.complaint_id', '=', 'c.id')
                    ->whereNull('cd.deleted_at')
                    ->whereIn('cd.source_type', ['warehouse', 'service_vehicle']);
            })
            ->whereIn('c.garage_id', $scope->garageIds)
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->whereIn('c.bus_id', function ($sub) use ($scope) {
                $sub->select('id')->from('buses')->where('brand_id', $scope->brandId);
            }))
            ->selectRaw('COUNT(DISTINCT c.id) as total_complaints')
            ->selectRaw('COUNT(cd.id) as total_parts')
            ->first();

        $totalComplaints = (int) ($row->total_complaints ?? 0);
        $totalParts = (int) ($row->total_parts ?? 0);

        return [
            'total_complaints' => $totalComplaints,
            'total_parts' => $totalParts,
            'avg_parts_per_complaint' => $totalComplaints > 0
                ? round($totalParts / $totalComplaints, 2)
                : 0.0,
        ];
    }
}
