<?php

namespace App\Services\Reports;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComplaintReportService
{
    /**
     * Summary of opened / closed cards within the period.
     */
    public function summary(ReportPeriod $period, ReportScope $scope): array
    {
        $base = Complaint::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->when($scope->userId, fn ($q) => $q->where('created_by', $scope->userId));

        $opened = (clone $base)
            ->whereBetween('created_at', [$period->from, $period->to])
            ->count();

        $closed = (clone $base)
            ->whereNotNull('closed_at')
            ->whereBetween('closed_at', [$period->from, $period->to])
            ->count();

        $openNow = (clone $base)
            ->where('status', '!=', 'completed')
            ->count();

        $byStatus = (clone $base)
            ->whereBetween('created_at', [$period->from, $period->to])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'opened'   => $opened,
            'closed'   => $closed,
            'open_now' => $openNow,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Top complaint types within the period.
     */
    public function topTypes(ReportPeriod $period, ReportScope $scope): Collection
    {
        return Complaint::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereBetween('created_at', [$period->from, $period->to])
            ->when($scope->userId, fn ($q) => $q->where('created_by', $scope->userId))
            ->select('complaint_type', DB::raw('COUNT(*) as total'))
            ->whereNotNull('complaint_type')
            ->groupBy('complaint_type')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Per-user action counts based on the audit log.
     */
    public function workerActivity(ReportPeriod $period, ReportScope $scope): Collection
    {
        $rows = AuditLog::query()
            ->where('auditable_type', Complaint::class)
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

    /**
     * Complaint count per bus within the period.
     */
    public function byBus(ReportPeriod $period, ReportScope $scope): Collection
    {
        return Complaint::withoutGlobalScope('garage')
            ->whereIn('complaints.garage_id', $scope->garageIds)
            ->whereBetween('complaints.created_at', [$period->from, $period->to])
            ->whereNull('complaints.deleted_at')
            ->when($scope->userId, fn ($q) => $q->where('complaints.created_by', $scope->userId))
            ->join('buses', 'buses.id', '=', 'complaints.bus_id')
            ->select(
                'buses.id as bus_id',
                'buses.dqn',
                'buses.route_number',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN complaints.status = 'completed' THEN 1 ELSE 0 END) as completed")
            )
            ->groupBy('buses.id', 'buses.dqn', 'buses.route_number')
            ->orderByDesc('total')
            ->limit(50)
            ->get();
    }

    /**
     * Average close time (in hours) for complaints closed in the period.
     */
    public function avgCloseTime(ReportPeriod $period, ReportScope $scope): array
    {
        $rows = Complaint::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereNotNull('closed_at')
            ->whereBetween('closed_at', [$period->from, $period->to])
            ->when($scope->userId, fn ($q) => $q->where('created_by', $scope->userId))
            ->get(['created_at', 'closed_at', 'complaint_type']);

        if ($rows->isEmpty()) {
            return [
                'overall_avg_hours' => null,
                'sample_count'      => 0,
                'by_type'           => collect(),
            ];
        }

        $durations = $rows->map(fn ($c) => $c->created_at->diffInHours($c->closed_at));

        $byType = $rows->groupBy('complaint_type')
            ->map(function ($group, $type) {
                $hours = $group->map(fn ($c) => $c->created_at->diffInHours($c->closed_at));

                return (object) [
                    'type'        => $type,
                    'count'       => $group->count(),
                    'avg_hours'   => round($hours->avg(), 1),
                    'min_hours'   => $hours->min(),
                    'max_hours'   => $hours->max(),
                ];
            })
            ->values();

        return [
            'overall_avg_hours' => round($durations->avg(), 1),
            'sample_count'      => $rows->count(),
            'by_type'           => $byType,
        ];
    }
}
