<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\ComplaintItem;
use App\Models\Warehouse;
use App\Enums\TransferStatus;
use App\Models\WarehouseTransfer;
use App\Services\GarageContext;

class DashboardController extends Controller
{
    /**
     * Dashboard for the currently selected garage.
     *
     * Access control is enforced by the route middleware:
     *   - auth              — user must be logged in
     *   - garage.selected   — user must have an active garage context
     *
     * The previous implementation also called Gate::authorize() against
     * a DashboardPolicy that always returned true — pure noise that
     * documented a rule the framework already enforced.
     */
    public function index()
    {
        $totalBuses = Bus::count();
        $activeBuses = Bus::where('is_active', true)->count();
        $activeComplaints = Complaint::where('status', '!=', 'completed')->count();
        $totalWarehouseItems = Warehouse::sum('quantity');

        $recentBuses = Bus::orderBy('id', 'desc')->limit(5)->get();

        // Use each item's own minimum_quantity threshold instead of a hardcoded value.
        $lowStockItems = Warehouse::whereColumn('quantity', '<=', 'minimum_quantity')
            ->orderBy('quantity', 'asc')
            ->limit(10)
            ->get();

        $recentComplaints = Complaint::with('bus', 'items')
            ->where('status', '!=', 'completed')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $recurringIssues = ComplaintItem::recurring(30)->get();
        // ─── Oil change stats ───
        // Eager-load 'oilChanges' so OilChangeStatusService does not fire
        // a query per (bus × type). The service already checks relationLoaded().
        $oilStats = $this->computeOilChangeStats();

        $today = now()->toDateString();

        $busesWithoutKmTodayQuery = Bus::whereDoesntHave('dailyKmRecords', function ($query) use ($today) {
            $query->whereDate('date', $today);
        });

        $busesWithoutKmTodayCount = $busesWithoutKmTodayQuery->count();

        $busesWithoutKmToday = (clone $busesWithoutKmTodayQuery)->limit(10)->get();

        // ─── Transfer notifications ───
        // Three counters that the dashboard surfaces as a "requires
        // attention" panel. All queries are scoped to the current
        // garage (either as source or as destination).
        $garageId = GarageContext::getGarageId();

        $outboundPending = WarehouseTransfer::query()
            ->visibleToGarage($garageId)
            ->outbound($garageId)
            ->where('status', TransferStatus::Dispatched->value)
            ->count();

        $inboundPending = WarehouseTransfer::query()
            ->visibleToGarage($garageId)
            ->inbound($garageId)
            ->where('status', TransferStatus::Dispatched->value)
            ->count();

        $disputedCount = WarehouseTransfer::query()
            ->visibleToGarage($garageId)
            ->where('status', TransferStatus::Disputed->value)
            ->count();

        // Only fetch the pending list when there is something to show.
        $pendingTransfers = ($outboundPending + $inboundPending + $disputedCount) > 0
            ? WarehouseTransfer::query()
                ->visibleToGarage($garageId)
                ->with(['fromGarage', 'toGarage', 'toServiceVehicle'])
                ->pending()
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : collect();

        return view('dashboard', compact(
                'totalBuses',
                'activeBuses',
                'activeComplaints',
                'totalWarehouseItems',
                'recentBuses',
                'lowStockItems',
                'recentComplaints',
                'recurringIssues',
                'busesWithoutKmToday',
                'busesWithoutKmTodayCount',
                'outboundPending',
                'inboundPending',
                'disputedCount',
                'pendingTransfers',
                'oilStats',
        ));
    }

    /**
     * Count active buses by oil-change status, across all three types.
     *
     * Returns:
     *   [
     *     'overdue'    => N,
     *     'critical'   => N,
     *     'due-soon'   => N,
     *     'ok'         => N,
     *     'no-history' => N,
     *     'total_attention' => overdue + critical + due-soon,
     *   ]
     */
    private function computeOilChangeStats(): array
    {
        $counts = [
            'overdue'         => 0,
            'critical'        => 0,
            'due-soon'        => 0,
            'ok'              => 0,
            'no-history'      => 0,
            'total_attention' => 0,
        ];

        $buses = Bus::with(['oilChanges', 'latestKmRecord'])
            ->where('is_active', true)
            ->get();

        if ($buses->isEmpty()) {
            return $counts;
        }

        $service = app(\App\Services\OilChange\OilChangeStatusService::class);

        foreach ($buses as $bus) {
            foreach (\App\Enums\OilType::cases() as $type) {
                $status = $service->forBus($bus, $type);

                if (isset($counts[$status->status])) {
                    $counts[$status->status]++;
                }
            }
        }

        $counts['total_attention'] = $counts['overdue']
            + $counts['critical']
            + $counts['due-soon'];

        return $counts;
    }
}
