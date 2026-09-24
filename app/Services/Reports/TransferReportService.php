<?php

namespace App\Services\Reports;

use App\Enums\TransferStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregations for the warehouse transfer report domain.
 *
 * All queries are scoped to the current report scope's garageIds
 * (a list of garages — for a Garage Admin it holds one id; for a
 * Director every garage of their company; for a SuperAdmin every
 * garage on the platform).
 *
 * A transfer is "visible" to a garage if that garage is either the
 * source OR the destination. That is why the queries below use a
 * whereIn on both columns.
 */
class TransferReportService
{
    /**
     * Headline counters for the period.
     */
    public function summary(ReportPeriod $period, ReportScope $scope): array
    {
        $base = $this->baseQuery($period, $scope);

        $byStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $byType = (clone $base)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        $outbound = (clone $base)->whereIn('from_garage_id', $scope->garageIds)->count();
        $inbound = (clone $base)->whereIn('to_garage_id', $scope->garageIds)->count();

        $totalItemsMoved = (int) WarehouseTransferItem::query()
            ->whereIn('transfer_id', (clone $base)->pluck('id'))
            ->sum('declared_quantity');

        $disputedCount = $byStatus[TransferStatus::Disputed->value] ?? 0;
        $receivedCount = $byStatus[TransferStatus::Received->value] ?? 0;
        $rejectedCount = $byStatus[TransferStatus::Rejected->value] ?? 0;

        $total = array_sum($byStatus);
        $disputeRate = $total > 0 ? round(($disputedCount / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'outbound' => $outbound,
            'inbound' => $inbound,
            'items_moved' => $totalItemsMoved,
            'disputed' => $disputedCount,
            'received' => $receivedCount,
            'rejected' => $rejectedCount,
            'dispute_rate' => $disputeRate,
            'by_status' => $byStatus,
            'by_type' => $byType,
        ];
    }

    /**
     * Transfers grouped by (from_garage, to_garage) pair.
     */
    public function byRoute(ReportPeriod $period, ReportScope $scope): Collection
    {
        return $this->baseQuery($period, $scope)
            ->join('garages as from_g', 'from_g.id', '=', 'warehouse_transfers.from_garage_id')
            ->leftJoin('garages as to_g', 'to_g.id', '=', 'warehouse_transfers.to_garage_id')
            ->select(
                'warehouse_transfers.from_garage_id',
                'warehouse_transfers.to_garage_id',
                'from_g.name as from_garage_name',
                'from_g.code as from_garage_code',
                'to_g.name as to_garage_name',
                'to_g.code as to_garage_code',
                DB::raw('COUNT(*) as total_transfers'),
                DB::raw('SUM(CASE WHEN warehouse_transfers.status = \'disputed\' THEN 1 ELSE 0 END) as disputed_count'),
                DB::raw('SUM(CASE WHEN warehouse_transfers.status = \'received\' THEN 1 ELSE 0 END) as received_count')
            )
            ->groupBy(
                'warehouse_transfers.from_garage_id',
                'warehouse_transfers.to_garage_id',
                'from_g.name',
                'from_g.code',
                'to_g.name',
                'to_g.code',
            )
            ->orderByDesc('total_transfers')
            ->get();
    }

    /**
     * Items moved most often across all transfers in the period.
     */
    public function topItems(ReportPeriod $period, ReportScope $scope): Collection
    {
        $transferIds = $this->baseQuery($period, $scope)->pluck('id');

        if ($transferIds->isEmpty()) {
            return collect();
        }

        return WarehouseTransferItem::query()
            ->whereIn('warehouse_transfer_items.transfer_id', $transferIds)
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_transfer_items.warehouse_id')
            ->select(
                'warehouses.code',
                DB::raw('MAX(warehouses.name) as name'),
                DB::raw('MAX(warehouses.unit) as unit'),
                DB::raw('COUNT(*) as times_transferred'),
                DB::raw('SUM(warehouse_transfer_items.declared_quantity) as total_declared'),
                DB::raw('SUM(COALESCE(warehouse_transfer_items.received_quantity, 0)) as total_received'),
            )
            ->groupBy('warehouses.code')
            ->orderByDesc('times_transferred')
            ->limit(50)
            ->get();
    }

    /**
     * Per-user activity, based on the audit log entries written for
     * WarehouseTransfer.
     */
    public function workerActivity(ReportPeriod $period, ReportScope $scope): Collection
    {
        $rows = AuditLog::query()
            ->where('auditable_type', WarehouseTransfer::class)
            ->whereBetween('audit_logs.created_at', [$period->from, $period->to])
            ->whereIn('audit_logs.garage_id', $scope->garageIds)
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

    /**
     * Disputed transfers with the discrepancy amounts.
     */
    public function disputed(ReportPeriod $period, ReportScope $scope): Collection
    {
        return $this->baseQuery($period, $scope)
            ->where('status', TransferStatus::Disputed->value)
            ->with(['fromGarage', 'toGarage', 'toServiceVehicle', 'items'])
            ->orderByDesc('received_at')
            ->limit(200)
            ->get();
    }

    /**
     * Flat, line-level transfer report:
     *   from → to, item code, item name, declared qty, received qty.
     *
     * One row per warehouse_transfer_items line, so the reader sees the
     * EXACT granularity of the shipment — not an aggregated total.
     */
    public function detailed(ReportPeriod $period, ReportScope $scope): Collection
    {
        $transferIds = $this->baseQuery($period, $scope)->pluck('id');

        if ($transferIds->isEmpty()) {
            return collect();
        }

        return WarehouseTransferItem::query()
            ->whereIn('warehouse_transfer_items.transfer_id', $transferIds)
            ->join('warehouse_transfers', 'warehouse_transfers.id', '=', 'warehouse_transfer_items.transfer_id')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_transfer_items.warehouse_id')
            ->leftJoin('garages as from_g', 'from_g.id', '=', 'warehouse_transfers.from_garage_id')
            ->leftJoin('garages as to_g', 'to_g.id', '=', 'warehouse_transfers.to_garage_id')
            ->leftJoin('service_vehicles', 'service_vehicles.id', '=', 'warehouse_transfers.to_service_vehicle_id')
            ->select(
                'warehouse_transfers.id as transfer_id',
                'warehouse_transfers.type',
                'warehouse_transfers.status',
                'warehouse_transfers.created_at',
                'warehouse_transfers.to_garage_id',
                'warehouse_transfers.to_service_vehicle_id',
                'from_g.name as from_garage_name',
                'from_g.code as from_garage_code',
                'to_g.name as to_garage_name',
                'to_g.code as to_garage_code',
                'service_vehicles.name as to_vehicle_name',
                'warehouses.code',
                'warehouses.name as part_name',
                'warehouses.unit',
                'warehouse_transfer_items.declared_quantity',
                'warehouse_transfer_items.received_quantity',
            )
            ->orderByDesc('warehouse_transfers.created_at')
            ->orderBy('warehouse_transfers.id')
            ->limit(1000)
            ->get();
    }
    // ==================== PRIVATE ====================

    /**
     * Base query: transfers whose created_at falls in the period and
     * whose source OR destination is one of the visible garages.
     *
     * IMPORTANT — every column is fully qualified with the table name
     * (`warehouse_transfers.created_at`, etc.) because callers like
     * `byRoute()` JOIN the `garages` table twice. Both `garages` and
     * `warehouse_transfers` have a `created_at` column, so an
     * unqualified reference triggers PostgreSQL error 42702
     * ("ambiguous column reference").
     */
    private function baseQuery(ReportPeriod $period, ReportScope $scope)
    {
        return WarehouseTransfer::query()
            ->whereBetween('warehouse_transfers.created_at', [$period->from, $period->to])
            ->where(function ($q) use ($scope) {
                $q->whereIn('warehouse_transfers.from_garage_id', $scope->garageIds)
                    ->orWhereIn('warehouse_transfers.to_garage_id', $scope->garageIds);
            });
    }
}
