<?php

namespace App\Services\Reports;

use App\Models\Complaint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fleet health aggregations — reports #20–#25.
 *
 * Covers:
 *   20. Cost per KM              — which bus is most expensive per km
 *   21. Downtime Analysis        — which bus was out of service longest
 *   22. Recurring Issues         — repeating problem descriptions
 *   23. Recurring Complaints     — same issue on the same bus 2+ times
 *   24. Accidents Report         — accident cards in the period
 *   25. Bus Utilization          — % of days each bus was in service
 */
class FleetHealthReportService
{
    // ==================================================================
    // #20 — COST PER KM
    // ==================================================================

    /**
     * Cost per kilometer for each active bus.
     *
     * Cost = sum of (used_quantity × price_at_use) across all details.
     * Distance = MAX(km) - MIN(km) from daily_km_records in the period.
     *
     * @return Collection<int, object>
     */
    public function costPerKm(ReportPeriod $period, ReportScope $scope): Collection
    {
        $rows = DB::table('buses as b')
            ->leftJoin('complaints as c', function ($join) use ($period) {
                $join->on('c.bus_id', '=', 'b.id')
                    ->whereNull('c.deleted_at')
                    ->whereBetween('c.created_at', [$period->from, $period->to]);
            })
            ->leftJoin('complaint_details as cd', function ($join) {
                $join->on('cd.complaint_id', '=', 'c.id')
                    ->whereNull('cd.deleted_at')
                    ->whereIn('cd.source_type', ['warehouse', 'service_vehicle']);
            })
            ->leftJoin('daily_km_records as dkr', function ($join) use ($period) {
                $join->on('dkr.bus_id', '=', 'b.id')
                    ->whereNull('dkr.deleted_at')
                    ->whereBetween('dkr.date', [
                        $period->from->toDateString(),
                        $period->to->toDateString(),
                    ]);
            })
            ->whereIn('b.garage_id', $scope->garageIds)
            ->where('b.is_active', true)
            ->whereNull('b.deleted_at')
            ->when($scope->brandId, fn ($q) => $q->where('b.brand_id', $scope->brandId))
            ->select(
                'b.id as bus_id',
                'b.dqn',
                'b.route_number',
                DB::raw('COALESCE(SUM(cd.used_quantity * COALESCE(cd.price_at_use, 0)), 0) as total_cost'),
                DB::raw('COALESCE(MAX(dkr.km) - MIN(dkr.km), 0) as distance'),
                DB::raw('COUNT(DISTINCT c.id) as cards_count'),
            )
            ->groupBy('b.id', 'b.dqn', 'b.route_number')
            ->get();

        return $rows
            ->map(function ($r) {
                $r->total_cost = (float) $r->total_cost;
                $r->distance = (int) $r->distance;
                $r->cost_per_km = $r->distance > 0
                    ? round($r->total_cost / $r->distance, 4)
                    : null;

                return $r;
            })
            ->filter(fn ($r) => $r->cost_per_km !== null)
            ->sortByDesc('cost_per_km')
            ->values();
    }

    // ==================================================================
    // #21 — DOWNTIME ANALYSIS
    // ==================================================================

    /**
     * Aggregate downtime per bus: how many hours each bus spent
     * outside of service within the period.
     *
     * Uses `closed_at - created_at` for closed complaints. Open
     * complaints are excluded from the total (their downtime is
     * still ongoing — they belong on the dashboard, not here).
     *
     * @return Collection<int, object>
     */
    public function downtime(ReportPeriod $period, ReportScope $scope): Collection
    {
        return DB::table('complaints as c')
            ->join('buses as b', 'b.id', '=', 'c.bus_id')
            ->whereIn('c.garage_id', $scope->garageIds)
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->whereNotNull('c.closed_at')
            ->when($scope->brandId, fn ($q) => $q->where('b.brand_id', $scope->brandId))
            ->select(
                'b.id as bus_id',
                'b.dqn',
                'b.route_number',
                DB::raw('COUNT(*) as complaint_count'),
                DB::raw('SUM(EXTRACT(EPOCH FROM (c.closed_at - c.created_at)) / 3600) as total_hours'),
                DB::raw('AVG(EXTRACT(EPOCH FROM (c.closed_at - c.created_at)) / 3600) as avg_hours'),
                DB::raw('MAX(EXTRACT(EPOCH FROM (c.closed_at - c.created_at)) / 3600) as max_hours'),
            )
            ->groupBy('b.id', 'b.dqn', 'b.route_number')
            ->orderByDesc('total_hours')
            ->get();
    }

    // ==================================================================
    // #22 — RECURRING ISSUES
    // ==================================================================

    /**
     * Complaint descriptions that appear 2+ times within the selected
     * period, broken down by bus.
     *
     * ────────────────────────────────────────────────────────────────
     * NOTE: this method deliberately does NOT use
     * ComplaintItem::scopeRecurring().
     *
     * The scope has two limitations that break the report for
     * Directors:
     *
     *   1. It reads GarageContext::resolveGarageId() — a single
     *      garage id. Directors have no garage context, so the scope
     *      returns `WHERE 1 = 0` and the report renders empty.
     *
     *   2. It ignores $period and $scope->brandId, so changing the
     *      date range or the brand filter has no effect.
     *
     * The query below uses $scope->garageIds (already resolved to the
     * correct set of garages by ReportScope::for()), respects the
     * period and the optional brand filter, and returns the same
     * column shape the blade expects (description, total,
     * last_occurrence).
     * ────────────────────────────────────────────────────────────────
     *
     * @return Collection<int, object>
     */
    public function recurringIssues(ReportPeriod $period, ReportScope $scope): Collection
    {
        return DB::table('complaint_items as ci')
            ->join('complaints as c', 'c.id', '=', 'ci.complaint_id')
            ->join('buses as b', 'b.id', '=', 'c.bus_id')
            ->whereIn('c.garage_id', $scope->garageIds)
            ->whereNull('c.deleted_at')
            ->whereNull('ci.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->where('b.brand_id', $scope->brandId))
                        ->where(function ($q) {
                // Recurring issues are a signal about ONGOING problems —
                // only pending and in_progress cards count. Cancelled
                // cards were never real problems, and completed cards
                // were resolved; neither should inflate this report.
                $q->whereIn('c.status', [
                    \App\Enums\ComplaintStatus::Pending->value,
                    \App\Enums\ComplaintStatus::InProgress->value,
                ]);
            })
            ->select(
                'ci.description',
                'b.id as bus_id',
                'b.dqn',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(c.created_at) as last_occurrence')
            )
            ->groupBy('ci.description', 'b.id', 'b.dqn')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('total')
            ->limit(200)
            ->get();
    }

    // ==================================================================
    // #23 — RECURRING COMPLAINTS ON SAME BUS
    // ==================================================================

    /**
     * Same complaint description appearing 2+ times on the same bus
     * within the period.
     *
     * @return Collection<int, object>
     */
    public function recurringComplaintsOnSameBus(ReportPeriod $period, ReportScope $scope): Collection
    {
        return DB::table('complaint_items as ci')
            ->join('complaints as c', 'c.id', '=', 'ci.complaint_id')
            ->join('buses as b', 'b.id', '=', 'c.bus_id')
            ->whereIn('c.garage_id', $scope->garageIds)
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->where('b.brand_id', $scope->brandId))
            ->select(
                'ci.description',
                'b.id as bus_id',
                'b.dqn',
                'b.route_number',
                DB::raw('COUNT(*) as occurrences'),
                DB::raw('MAX(c.created_at) as last_occurrence'),
                DB::raw('MIN(c.created_at) as first_occurrence'),
            )
            ->groupBy('ci.description', 'b.id', 'b.dqn', 'b.route_number')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('occurrences')
            ->limit(200)
            ->get();
    }

    // ==================================================================
    // #24 — ACCIDENTS REPORT
    // ==================================================================

    /**
     * All accident cards in the period.
     *
     * @return Collection<int, Complaint>
     */
    public function accidents(ReportPeriod $period, ReportScope $scope): Collection
    {
        return Complaint::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->where('complaint_type', 'accident')
            ->whereBetween('created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->whereHas(
                'bus',
                fn ($bq) => $bq->where('brand_id', $scope->brandId)
            ))
            ->with([
                'bus:id,dqn,route_number,bus_project',
                'driver:id,code,first_name,last_name',
                'serviceVehicle:id,name,plate_number',
            ])
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();
    }

    // ==================================================================
    // #25 — BUS UTILIZATION
    // ==================================================================

    /**
     * Percent of days each bus was in service during the period.
     *
     * "In service" = has at least one daily_km_record (i.e. the bus
     * actually drove). Days without a KM record are counted as
     * out-of-service.
     *
     * @return Collection<int, object>
     */
    public function utilization(ReportPeriod $period, ReportScope $scope): Collection
    {
        $totalDays = $period->days();

        $rows = DB::table('buses as b')
            ->leftJoin('daily_km_records as dkr', function ($join) use ($period) {
                $join->on('dkr.bus_id', '=', 'b.id')
                    ->whereNull('dkr.deleted_at')
                    ->whereBetween('dkr.date', [
                        $period->from->toDateString(),
                        $period->to->toDateString(),
                    ]);
            })
            ->leftJoin('bus_daily_statuses as bds', function ($join) use ($period) {
                $join->on('bds.bus_id', '=', 'b.id')
                    ->whereNull('bds.deleted_at')
                    ->whereBetween('bds.date', [
                        $period->from->toDateString(),
                        $period->to->toDateString(),
                    ]);
            })
            ->whereIn('b.garage_id', $scope->garageIds)
            ->where('b.is_active', true)
            ->whereNull('b.deleted_at')
            ->when($scope->brandId, fn ($q) => $q->where('b.brand_id', $scope->brandId))
            ->select(
                'b.id as bus_id',
                'b.dqn',
                'b.route_number',
                DB::raw('COUNT(DISTINCT dkr.date) as days_in_service'),
                DB::raw('COUNT(DISTINCT bds.date) as days_with_status'),
                DB::raw('COALESCE(MAX(dkr.km) - MIN(dkr.km), 0) as total_distance'),
            )
            ->groupBy('b.id', 'b.dqn', 'b.route_number')
            ->get();

        return $rows
            ->map(function ($r) use ($totalDays) {
                $r->days_in_service = (int) $r->days_in_service;
                $r->days_with_status = (int) $r->days_with_status;
                $r->total_distance = (int) $r->total_distance;
                $r->total_days_in_period = $totalDays;
                $r->utilization_percent = $totalDays > 0
                    ? round(($r->days_in_service / $totalDays) * 100, 1)
                    : 0.0;

                return $r;
            })
            ->sortByDesc('utilization_percent')
            ->values();
    }
}
