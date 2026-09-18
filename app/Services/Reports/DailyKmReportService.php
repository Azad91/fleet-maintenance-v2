<?php

namespace App\Services\Reports;

use App\Models\AuditLog;
use App\Models\Bus;
use App\Models\DailyKmRecord;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyKmReportService
{
    /**
     * Buses with no KM record for TODAY.
     *
     * The brand filter narrows the list to a single manufacturer.
     */
    public function missing(ReportPeriod $period, ReportScope $scope): Collection
    {
        $targetDate = now()->toDateString();

        return Bus::withoutGlobalScope('garage')
            ->whereIn('buses.garage_id', $scope->garageIds)
            ->where('buses.is_active', true)
            ->whereNull('buses.deleted_at')
            ->when($scope->brandId, fn ($q) => $q->where('buses.brand_id', $scope->brandId))
            ->whereDoesntHave('dailyKmRecords', function ($q) use ($targetDate) {
                $q->whereDate('date', $targetDate);
            })
            ->orderBy('buses.dqn')
            ->get(['buses.id', 'buses.dqn', 'buses.route_number', 'buses.bus_project', 'buses.km', 'buses.brand_id']);
    }

    /**
     * Top buses by total distance driven within the period.
     */
    public function topBuses(ReportPeriod $period, ReportScope $scope): Collection
    {
        return DailyKmRecord::withoutGlobalScope('garage')
            ->whereIn('daily_km_records.garage_id', $scope->garageIds)
            ->whereBetween('daily_km_records.date', [$period->from->toDateString(), $period->to->toDateString()])
            ->whereNull('daily_km_records.deleted_at')
            ->when($scope->userId, fn ($q) => $q->where('daily_km_records.created_by', $scope->userId))
            ->join('buses', 'buses.id', '=', 'daily_km_records.bus_id')
            ->when($scope->brandId, fn ($q) => $q->where('buses.brand_id', $scope->brandId))
            ->select(
                'buses.id as bus_id',
                'buses.dqn',
                'buses.route_number',
                DB::raw('MIN(daily_km_records.km) as start_km'),
                DB::raw('MAX(daily_km_records.km) as end_km'),
                DB::raw('COUNT(*) as entries_count')
            )
            ->groupBy('buses.id', 'buses.dqn', 'buses.route_number')
            ->get()
            ->map(function ($row) {
                $row->distance = max(0, $row->end_km - $row->start_km);

                return $row;
            })
            ->sortByDesc('distance')
            ->take(20)
            ->values();
    }

    /**
     * Per-user action counts based on the audit log.
     *
     * The brand filter is intentionally NOT applied — see the
     * ComplaintReportService for the rationale.
     */
    public function workerActivity(ReportPeriod $period, ReportScope $scope): Collection
    {
        $rows = AuditLog::query()
            ->where('auditable_type', DailyKmRecord::class)
            ->whereBetween('audit_logs.created_at', [$period->from, $period->to])
            ->whereIn('audit_logs.garage_id', $scope->garageIds)
            ->when($scope->userId, fn ($q) => $q->where('audit_logs.user_id', $scope->userId))
            ->select(
                'user_id',
                DB::raw('COUNT(*) as total_actions'),
                DB::raw("SUM(CASE WHEN event = 'created' THEN 1 ELSE 0 END) as created_count"),
                DB::raw("SUM(CASE WHEN event = 'updated' THEN 1 ELSE 0 END) as updated_count"),
                DB::raw("SUM(CASE WHEN event IN ('deleted','force_deleted') THEN 1 ELSE 0 END) as deleted_count")
            )
            ->groupBy('user_id')
            ->orderByDesc('total_actions')
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'user' => $users->get($row->user_id),
            'total_actions' => (int) $row->total_actions,
            'created_count' => (int) $row->created_count,
            'updated_count' => (int) $row->updated_count,
            'deleted_count' => (int) $row->deleted_count,
        ]);
    }
}
