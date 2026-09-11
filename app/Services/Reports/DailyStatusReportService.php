<?php

namespace App\Services\Reports;

use App\Models\AuditLog;
use App\Models\BusDailyStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyStatusReportService
{
    /**
     * Distribution of statuses in the period.
     * Returns counts grouped by status text with total sample size.
     */
    public function distribution(ReportPeriod $period, ReportScope $scope): array
    {
        $base = BusDailyStatus::withoutGlobalScope('garage')
            ->whereIn('bus_daily_statuses.garage_id', $scope->garageIds)
            ->whereBetween('bus_daily_statuses.date', [$period->from->toDateString(), $period->to->toDateString()])
            ->whereNull('bus_daily_statuses.deleted_at')
            ->when($scope->userId, fn ($q) => $q->where('bus_daily_statuses.created_by', $scope->userId));

        $rows = (clone $base)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $total = $rows->sum('total');

        return [
            'rows'  => $rows,
            'total' => $total,
        ];
    }

    /**
     * Status changes timeline — the audit log for BusDailyStatus in the period.
     */
    public function changes(ReportPeriod $period, ReportScope $scope): Collection
    {
        return AuditLog::query()
            ->where('auditable_type', BusDailyStatus::class)
            ->whereBetween('audit_logs.created_at', [$period->from, $period->to])
            ->whereIn('audit_logs.garage_id', $scope->garageIds)
            ->when($scope->userId, fn ($q) => $q->where('audit_logs.user_id', $scope->userId))
            ->with('user')
            ->orderByDesc('audit_logs.id')
            ->limit(200)
            ->get();
    }

    /**
     * Per-user action counts based on the audit log.
     */
    public function workerActivity(ReportPeriod $period, ReportScope $scope): Collection
    {
        $rows = AuditLog::query()
            ->where('auditable_type', BusDailyStatus::class)
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
            'user'          => $users->get($row->user_id),
            'total_actions' => (int) $row->total_actions,
            'created_count' => (int) $row->created_count,
            'updated_count' => (int) $row->updated_count,
            'deleted_count' => (int) $row->deleted_count,
        ]);
    }
}
