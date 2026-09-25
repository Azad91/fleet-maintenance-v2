<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Warehouse analytics aggregations — reports #26, #28, #29, #30, #31.
 *
 * Complements the operational WarehouseReportService (receipt, usage,
 * worker activity, low stock, movement) with analytical views that
 * answer inventory-management questions:
 *
 *   26. Slow-moving Stock          — items idle for N days
 *   28. Inventory Valuation        — total value, category breakdown
 *   29. Reorder Suggestions        — what to reorder and how much
 *   30. Supplier Performance       — supplier usage statistics
 *   31. Part Movement History      — per-part per-bus chronological log
 */
class WarehouseAnalyticsReportService
{
    // ==================================================================
    // #26 — SLOW-MOVING STOCK
    // ==================================================================

    /**
     * Warehouse items that have not been consumed in the last N days.
     *
     * "Consumed" = a complaint_details row with used_quantity > 0.
     * Items that have never been consumed at all are excluded — those
     * belong to the "Dead Stock" report (#27).
     *
     * @return Collection<int, object>
     */
    public function slowMoving(ReportScope $scope, int $days = 90): Collection
    {
        $cutoff = now()->subDays($days);

        return DB::table('warehouses as w')
            ->whereIn('w.garage_id', $scope->garageIds)
            ->whereNull('w.deleted_at')
            ->where('w.is_quarantine', false)
            ->where('w.quantity', '>', 0)
            ->whereExists(function ($sub) {
                // Item must have been used at least once historically —
                // otherwise it belongs to dead-stock, not slow-moving.
                $sub->select(DB::raw(1))
                    ->from('complaint_details as cd')
                    ->whereNull('cd.deleted_at')
                    ->whereColumn('cd.code', 'w.code')
                    ->whereColumn('cd.garage_id', 'w.garage_id');
            })
            ->whereNotExists(function ($sub) use ($cutoff) {
                // ...but NOT in the last N days.
                $sub->select(DB::raw(1))
                    ->from('complaint_details as cd')
                    ->whereNull('cd.deleted_at')
                    ->whereColumn('cd.code', 'w.code')
                    ->whereColumn('cd.garage_id', 'w.garage_id')
                    ->where('cd.created_at', '>=', $cutoff);
            })
            ->select(
                'w.id',
                'w.code',
                'w.name',
                'w.quantity',
                'w.unit',
                'w.price',
                'w.category',
                DB::raw('(w.quantity * COALESCE(w.price, 0)) as tied_capital'),
                DB::raw('(
                    SELECT MAX(cd2.created_at)
                    FROM complaint_details cd2
                    WHERE cd2.code = w.code
                      AND cd2.garage_id = w.garage_id
                      AND cd2.deleted_at IS NULL
                ) as last_used_at'),
            )
            ->orderByDesc('tied_capital')
            ->get()
            ->map(function ($row) {
                $row->tied_capital = (float) $row->tied_capital;
                $row->days_since_use = $row->last_used_at
                    ? (int) \Carbon\Carbon::parse($row->last_used_at)->diffInDays(now())
                    : null;

                return $row;
            });
    }

    // ==================================================================
    // #28 — INVENTORY VALUATION
    // ==================================================================

    /**
     * Total inventory value, aggregated by category.
     *
     * @return array{
     *     total_value: float,
     *     total_items: int,
     *     total_quantity: int,
     *     by_category: Collection<int, object>
     * }
     */
    public function valuation(ReportScope $scope): array
    {
        $rows = DB::table('warehouses')
            ->whereIn('garage_id', $scope->garageIds)
            ->whereNull('deleted_at')
            ->where('is_quarantine', false)
            ->select(
                DB::raw("COALESCE(NULLIF(category, ''), 'uncategorized') as category_key"),
                DB::raw('COUNT(*) as item_count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(quantity * COALESCE(price, 0)) as total_value'),
            )
            ->groupBy('category_key')
            ->orderByDesc('total_value')
            ->get();

        $totalValue = (float) $rows->sum('total_value');
        $totalItems = (int) $rows->sum('item_count');
        $totalQuantity = (int) $rows->sum('total_quantity');

        return [
            'total_value' => $totalValue,
            'total_items' => $totalItems,
            'total_quantity' => $totalQuantity,
            'by_category' => $rows,
        ];
    }

    // ==================================================================
    // #29 — REORDER SUGGESTIONS
    // ==================================================================

    /**
     * Items at or below minimum quantity, with a suggested order size.
     *
     * Suggestion logic:
     *   target = max(minimum_quantity * 2, last_30_days_usage * 2)
     *   suggest = max(0, target - current_quantity)
     *
     * If the item has not been used in the last 30 days, the suggestion
     * is simply minimum_quantity * 2 - current_quantity.
     *
     * @return Collection<int, object>
     */
    public function reorderSuggestions(ReportScope $scope): Collection
    {
        $thirtyDaysAgo = now()->subDays(30);

        return DB::table('warehouses as w')
            ->whereIn('w.garage_id', $scope->garageIds)
            ->whereNull('w.deleted_at')
            ->where('w.is_quarantine', false)
            ->whereColumn('w.quantity', '<=', 'w.minimum_quantity')
            ->select(
                'w.id',
                'w.code',
                'w.name',
                'w.quantity',
                'w.minimum_quantity',
                'w.unit',
                'w.price',
                'w.supplier',
            )
            ->selectRaw(
                '(SELECT COALESCE(SUM(cd.used_quantity), 0)
                    FROM complaint_details cd
                    WHERE cd.code = w.code
                      AND cd.garage_id = w.garage_id
                      AND cd.deleted_at IS NULL
                      AND cd.created_at >= ?) as usage_last_30d',
                [$thirtyDaysAgo]
            )
            ->orderByRaw('(w.minimum_quantity - w.quantity) DESC')
            ->get()
            ->map(function ($row) {
                $row->usage_last_30d = (int) $row->usage_last_30d;
                $row->deficit = max(0, (int) $row->minimum_quantity - (int) $row->quantity);

                $target = max(
                    (int) $row->minimum_quantity * 2,
                    (int) $row->usage_last_30d * 2
                );

                $row->suggested_order = max(0, $target - (int) $row->quantity);
                $row->estimated_cost = (float) $row->suggested_order * (float) ($row->price ?? 0);

                return $row;
            });
    }

    // ==================================================================
    // #30 — SUPPLIER PERFORMANCE
    // ==================================================================

    /**
     * Usage statistics per supplier.
     *
     * "Usage" = sum of used_quantity across complaint_details where the
     * part's warehouse row has this supplier. Provides a signal for
     * supplier-quality reviews: high usage of parts from a supplier
     * might indicate quality issues.
     *
     * @return Collection<int, object>
     */
    public function supplierPerformance(ReportPeriod $period, ReportScope $scope): Collection
    {
        return DB::table('complaint_details as cd')
            ->join('warehouses as w', function ($join) {
                $join->on('w.code', '=', 'cd.code')
                    ->on('w.garage_id', '=', 'cd.garage_id')
                    ->whereNull('w.deleted_at');
            })
            ->join('complaints as c', 'c.id', '=', 'cd.complaint_id')
            ->whereIn('cd.garage_id', $scope->garageIds)
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->whereNotNull('w.supplier')
            ->where('w.supplier', '!=', '')
            ->where('cd.used_quantity', '>', 0)
            ->select(
                'w.supplier',
                DB::raw('COUNT(DISTINCT w.code) as distinct_parts'),
                DB::raw('COUNT(DISTINCT c.id) as cards_count'),
                DB::raw('SUM(cd.used_quantity) as total_used'),
                DB::raw('SUM(cd.used_quantity * COALESCE(cd.price_at_use, 0)) as total_cost'),
            )
            ->groupBy('w.supplier')
            ->orderByDesc('total_used')
            ->get();
    }

    // ==================================================================
    // #31 — PART MOVEMENT HISTORY
    // ==================================================================

    /**
     * Chronological log: every part consumption with its bus, card,
     * date, quantity, price, and source. Supports filtering by part
     * code and bus DQN.
     *
     * @return Collection<int, object>
     */
    public function partMovementHistory(
        ReportPeriod $period,
        ReportScope $scope,
        ?string $code = null,
        ?string $dqn = null
    ): Collection {
        return DB::table('complaint_details as cd')
            ->join('complaints as c', 'c.id', '=', 'cd.complaint_id')
            ->join('buses as b', 'b.id', '=', 'c.bus_id')
            ->whereIn('cd.garage_id', $scope->garageIds)
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$period->from, $period->to])
            ->where('cd.used_quantity', '>', 0)
            ->whereIn('cd.source_type', ['warehouse', 'service_vehicle'])
            ->when($code, fn ($q) => $q->where('cd.code', 'ILIKE', "%{$code}%"))
            ->when($dqn, fn ($q) => $q->where('b.dqn', 'ILIKE', "%{$dqn}%"))
            ->when($scope->brandId, fn ($q) => $q->where('b.brand_id', $scope->brandId))
            ->select(
                'cd.id as detail_id',
                'cd.code',
                'cd.name as part_name',
                'cd.used_quantity',
                'cd.price_at_use',
                'cd.source_type',
                'c.id as complaint_id',
                'c.created_at',
                'b.id as bus_id',
                'b.dqn',
                'b.route_number',
            )
            ->orderByDesc('c.created_at')
            ->orderByDesc('cd.id')
            ->limit(1000)
            ->get()
            ->map(function ($row) {
                $row->line_cost = (float) $row->used_quantity * (float) ($row->price_at_use ?? 0);

                return $row;
            });
    }
}
