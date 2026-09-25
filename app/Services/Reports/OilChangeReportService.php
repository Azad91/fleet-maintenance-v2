<?php

namespace App\Services\Reports;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusOilChange;
use App\Models\MotorOilDetail;
use App\Services\OilChange\OilChangeStatusService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregations for the oil-change report domain.
 *
 * Covers reports #11–#15 from the report specification:
 *   11. Oil Change History          — full log per bus, filterable
 *   12. Upcoming (next 30 days)     — buses approaching their due km
 *   13. Monthly/Quarterly Count     — aggregate counts by oil type
 *   14. Schedule Adherence          — early / on-time / late breakdown
 *   15. Motor Oil Catalog Usage     — which oil brands/codes are used most
 */
class OilChangeReportService
{
    public function __construct(
        protected OilChangeStatusService $statusService,
    ) {}

    // ==================================================================
    // #11 — OIL CHANGE HISTORY
    // ==================================================================

    /**
     * Full oil change history for the period.
     *
     * @return Collection<int, BusOilChange>
     */
    public function history(ReportPeriod $period, ReportScope $scope, ?OilType $type = null): Collection
    {
        return BusOilChange::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->whereHas(
                'bus',
                fn ($bq) => $bq->where('brand_id', $scope->brandId)
            ))
            ->when($type, fn ($q) => $q->where('oil_type', $type->value))
            ->with('bus:id,dqn,route_number,brand_id')
            ->orderByDesc('actual_km')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();
    }

    // ==================================================================
    // #12 — UPCOMING (NEXT 30 DAYS)
    // ==================================================================

    /**
     * Buses whose next oil change is due within the next 30 days.
     *
     * We estimate "days remaining" using the fleet's average daily km
     * over the period. If a bus has no recent KM records, we skip it.
     *
     * @return Collection<int, array{bus: Bus, type: OilType, status: object, days_remaining: int}>
     */
    public function upcoming(ReportPeriod $period, ReportScope $scope): Collection
    {
        $avgDailyKm = $this->averageDailyKm($scope);

        $buses = Bus::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->when($scope->brandId, fn ($q) => $q->where('brand_id', $scope->brandId))
            ->with([
                'latestKmRecord',
                'latestMotorOilChange',
                'latestGearboxOilChange',
                'latestAxleOilChange',
            ])
            ->get();

        $rows = collect();

        foreach ($buses as $bus) {
            foreach (OilType::cases() as $type) {
                $status = $this->statusService->forBus($bus, $type);

                if ($status->remainingKm === null || $status->remainingKm < 0) {
                    continue; // overdue or no history — handled elsewhere
                }

                if ($avgDailyKm <= 0) {
                    continue;
                }

                $daysRemaining = (int) ceil($status->remainingKm / $avgDailyKm);

                if ($daysRemaining > 30) {
                    continue;
                }

                $rows->push([
                    'bus' => $bus,
                    'type' => $type,
                    'status' => $status,
                    'days_remaining' => $daysRemaining,
                ]);
            }
        }

        return $rows->sortBy('days_remaining')->values();
    }

    /**
     * Fleet-wide average km/day for the current scope.
     */
    private function averageDailyKm(ReportScope $scope): float
    {
        $row = DB::table('daily_km_records')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereNull('deleted_at')
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->selectRaw('SUM(km) as total_km, COUNT(DISTINCT date) as days')
            ->first();

        if (! $row || (int) $row->days === 0) {
            return 0.0;
        }

        // SUM(km) here is the total ODOMETER value, not the delta.
        // For an accurate estimate we need the diff between the
        // highest and lowest km per bus, then divide by days.
        return $this->fleetDailyDistance($scope);
    }

    /**
     * Compute the fleet's true daily distance by subtracting the
     * odometer delta per bus over the last 30 days.
     */
    private function fleetDailyDistance(ReportScope $scope): float
    {
        $rows = DB::table('daily_km_records')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereNull('deleted_at')
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->select('bus_id')
            ->selectRaw('MAX(km) - MIN(km) as distance')
            ->selectRaw('COUNT(DISTINCT date) as days')
            ->groupBy('bus_id')
            ->get();

        $totalDistance = 0;
        $totalDays = 0;

        foreach ($rows as $row) {
            $totalDistance += max(0, (int) $row->distance);
            $totalDays += (int) $row->days;
        }

        return $totalDays > 0 ? $totalDistance / $totalDays : 0.0;
    }

    // ==================================================================
    // #13 — MONTHLY / QUARTERLY COUNT
    // ==================================================================

    /**
     * Count of oil changes grouped by oil type and time bucket.
     *
     * @param  string  $bucket  'month' | 'quarter'
     * @return Collection<int, object>
     */
    public function countsByPeriod(
        ReportPeriod $period,
        ReportScope $scope,
        string $bucket = 'month'
    ): Collection {
        $trunc = $bucket === 'quarter' ? 'quarter' : 'month';

        return BusOilChange::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->whereHas(
                'bus',
                fn ($bq) => $bq->where('brand_id', $scope->brandId)
            ))
            ->select('oil_type')
            ->selectRaw("DATE_TRUNC('{$trunc}', created_at) as period_start")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('oil_type', 'period_start')
            ->orderByDesc('period_start')
            ->orderBy('oil_type')
            ->get();
    }

    // ==================================================================
    // #14 — SCHEDULE ADHERENCE
    // ==================================================================

    /**
     * Adherence to scheduled km intervals.
     *
     * For each oil change in the period, classify:
     *   - early:    actual_km < scheduled_km
     *   - on_time:  actual_km == scheduled_km
     *   - late:     actual_km > scheduled_km
     *
     * Only rows with a non-null scheduled_km are counted — imported
     * gearbox/axle rows often have no schedule.
     *
     * @return array{early: int, on_time: int, late: int, total: int, on_time_rate: float}
     */
    public function scheduleAdherence(ReportPeriod $period, ReportScope $scope): array
    {
        $row = BusOilChange::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$period->from, $period->to])
            ->whereNotNull('scheduled_km')
            ->when($scope->brandId, fn ($q) => $q->whereHas(
                'bus',
                fn ($bq) => $bq->where('brand_id', $scope->brandId)
            ))
            ->selectRaw("
                SUM(CASE WHEN actual_km <  scheduled_km THEN 1 ELSE 0 END) as early,
                SUM(CASE WHEN actual_km =  scheduled_km THEN 1 ELSE 0 END) as on_time,
                SUM(CASE WHEN actual_km >  scheduled_km THEN 1 ELSE 0 END) as late,
                COUNT(*) as total
            ")
            ->first();

        $early = (int) ($row->early ?? 0);
        $onTime = (int) ($row->on_time ?? 0);
        $late = (int) ($row->late ?? 0);
        $total = (int) ($row->total ?? 0);

        return [
            'early' => $early,
            'on_time' => $onTime,
            'late' => $late,
            'total' => $total,
            'on_time_rate' => $total > 0 ? round((($onTime + $early) / $total) * 100, 1) : 0.0,
        ];
    }

    // ==================================================================
    // #15 — MOTOR OIL CATALOG USAGE
    // ==================================================================

    /**
     * Which motor-oil catalog entries (part_code / km / brand) were
     * actually used in the period.
     *
     * We join complaint_details (source of truth for what was
     * consumed) with motor_oil_details (the catalog) by part_code.
     *
     * @return Collection<int, object>
     */
    public function catalogUsage(ReportPeriod $period, ReportScope $scope): Collection
    {
        return DB::table('complaint_details as cd')
            ->join('complaints as c', 'c.id', '=', 'cd.complaint_id')
            ->leftJoin('motor_oil_details as mod', function ($join) {
                $join->on('mod.part_code', '=', 'cd.code')
                    ->on('mod.garage_id', '=', 'cd.garage_id');
            })
            ->whereIn('cd.garage_id', $scope->garageIds)
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->when($scope->brandId, fn ($q) => $q->whereIn('c.bus_id', function ($sub) use ($scope) {
                $sub->select('id')->from('buses')->where('brand_id', $scope->brandId);
            }))
            ->select(
                'cd.code',
                DB::raw('MAX(cd.name) as part_name'),
                DB::raw('MAX(mod.km) as catalog_km'),
                DB::raw('MAX(mod.part_name) as catalog_name'),
                DB::raw('SUM(cd.used_quantity) as total_used'),
                DB::raw('COUNT(DISTINCT cd.complaint_id) as times_used'),
                DB::raw('SUM(cd.used_quantity * COALESCE(cd.price_at_use, 0)) as total_cost'),
            )
            ->groupBy('cd.code')
            ->orderByDesc('total_used')
            ->get();
    }

    // ==================================================================
    // #27 — DEAD STOCK (warehouse items never consumed)
    // ==================================================================

    /**
     * Warehouse items that have NEVER been consumed in any complaint.
     *
     * This is a "current state" report — no period filter, because
     * "never used" means all-time zero usage.
     *
     * @return Collection<int, object>
     */
    public function deadStock(ReportScope $scope): Collection
    {
        return DB::table('warehouses as w')
            ->whereIn('w.garage_id', $scope->garageIds)
            ->whereNull('w.deleted_at')
            ->where('w.is_quarantine', false)
            ->where('w.quantity', '>', 0)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('complaint_details as cd')
                    ->whereNull('cd.deleted_at')
                    ->whereColumn('cd.code', 'w.code')
                    ->whereColumn('cd.garage_id', 'w.garage_id');
            })
            ->select(
                'w.id',
                'w.code',
                'w.name',
                'w.quantity',
                'w.minimum_quantity',
                'w.unit',
                'w.price',
                'w.category',
                'w.created_at',
                DB::raw('(w.quantity * COALESCE(w.price, 0)) as tied_capital'),
            )
            ->orderByDesc('tied_capital')
            ->get();
    }
}
