<?php

namespace App\Services\Reports;

use App\Models\AuditLog;
use App\Models\ComplaintDetail;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseReportService
{
    /**
     * Items created within the period — a proxy for "receipts".
     * (No dedicated stock movement table exists yet.)
     */
    public function receipt(ReportPeriod $period, ReportScope $scope): Collection
    {
        return Warehouse::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereBetween('created_at', [$period->from, $period->to])
            ->when($scope->userId, fn ($q) => $q->where('created_by', $scope->userId))
            ->with('creator')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();
    }

    /**
     * Top used parts across complaint details in the period.
     * No user filter — this is a garage-wide usage report.
     */
    public function usage(ReportPeriod $period, ReportScope $scope): Collection
    {
        return ComplaintDetail::withoutGlobalScope('garage')
            ->whereIn('complaint_details.garage_id', $scope->garageIds)
            ->whereNull('complaint_details.deleted_at')
            ->whereHas('complaint', function ($q) use ($period) {
                $q->whereBetween('complaints.created_at', [$period->from, $period->to])
                    ->whereNull('complaints.deleted_at');
            })
            ->select(
                'code',
                DB::raw('MAX(name) as name'),
                DB::raw('SUM(used_quantity) as total_used'),
                DB::raw('COUNT(DISTINCT complaint_id) as times_used')
            )
            ->groupBy('code')
            ->orderByDesc('total_used')
            ->limit(50)
            ->get();
    }

    /**
     * Per-user action counts based on the audit log.
     */
    public function workerActivity(ReportPeriod $period, ReportScope $scope): Collection
    {
        $rows = AuditLog::query()
            ->where('auditable_type', Warehouse::class)
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

        // Hydrate user objects in one query
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

    /**
     * Current inventory below minimum threshold.
     * No period filter — this is a current-state snapshot.
     */
    public function lowStock(ReportScope $scope): Collection
    {
        return Warehouse::withoutGlobalScope('garage')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereColumn('quantity', '<=', 'minimum_quantity')
            ->orderBy('quantity')
            ->orderBy('name')
            ->get();
    }

    /**
     * Raw audit log entries for Warehouse (per user, per period).
     */
    public function movement(ReportPeriod $period, ReportScope $scope): Collection
    {
        return AuditLog::query()
            ->where('auditable_type', Warehouse::class)
            ->whereBetween('audit_logs.created_at', [$period->from, $period->to])
            ->whereIn('audit_logs.garage_id', $scope->garageIds)
            ->when($scope->userId, fn ($q) => $q->where('audit_logs.user_id', $scope->userId))
            ->with('user')
            ->orderByDesc('audit_logs.id')
            ->limit(200)
            ->get();
    }

    /**
     * Per-vehicle part usage — "which service vehicle consumed how many
     * parts, and which parts specifically".
     *
     * Only counts complaint_details rows whose source_type is
     * 'service_vehicle' (i.e. parts that came off a service vehicle, not
     * off the warehouse). The complaint must have a linked vehicle;
     * legacy rows without one are ignored here.
     */
    public function serviceVehicleUsage(ReportPeriod $period, ReportScope $scope): Collection
    {
        return ComplaintDetail::withoutGlobalScope('garage')
            ->whereIn('complaint_details.garage_id', $scope->garageIds)
            ->whereNull('complaint_details.deleted_at')
            ->where('complaint_details.source_type', 'service_vehicle')
            ->whereHas('complaint', function ($q) use ($period) {
                $q->whereBetween('complaints.created_at', [$period->from, $period->to])
                    ->whereNull('complaints.deleted_at')
                    ->whereNotNull('complaints.service_vehicle_id');
            })
            ->join('complaints', 'complaints.id', '=', 'complaint_details.complaint_id')
            ->join('service_vehicles', 'service_vehicles.id', '=', 'complaints.service_vehicle_id')
            ->select(
                'service_vehicles.id as service_vehicle_id',
                'service_vehicles.name as service_vehicle_name',
                'service_vehicles.plate_number as service_vehicle_plate',
                'complaint_details.code',
                DB::raw('MAX(complaint_details.name) as part_name'),
                DB::raw('SUM(complaint_details.used_quantity) as total_used'),
                DB::raw('COUNT(DISTINCT complaint_details.complaint_id) as times_used')
            )
            ->groupBy(
                'service_vehicles.id',
                'service_vehicles.name',
                'service_vehicles.plate_number',
                'complaint_details.code'
            )
            ->orderBy('service_vehicles.name')
            ->orderByDesc('total_used')
            ->get();
    }
}
