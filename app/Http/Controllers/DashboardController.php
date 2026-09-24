<?php

namespace App\Http\Controllers;

use App\Enums\OilType;
use App\Enums\TransferStatus;
use App\Models\Bus;
use App\Models\Complaint;
use App\Models\ComplaintItem;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\GarageContext;
use App\Services\OilChange\OilChangeStatusService;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /**
     * Dashboard for the currently selected garage.
     *
     * Access control is enforced by the route middleware:
     *   - auth              — user must be logged in
     *   - garage.selected   — user must have an active garage context
     */
    public function index()
    {
        // ────────────────────────────────────────────────────────────
        // KPI aggregation — one query instead of two separate COUNTs.
        // The CASE/SUM pattern is well supported by PostgreSQL and
        // avoids a second full scan on the buses table.
        // ────────────────────────────────────────────────────────────
        $busStats = Bus::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active THEN 1 ELSE 0 END) as active')
            ->first();

        $totalBuses = (int) ($busStats->total ?? 0);
        $activeBuses = (int) ($busStats->active ?? 0);

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

        // ────────────────────────────────────────────────────────────
        // Oil change stats + alerts — ONE bus query, ONE pass.
        // ────────────────────────────────────────────────────────────
        [$oilStats, $oilAlerts] = $this->computeOilChangeData();

        $today = now()->toDateString();

        $busesWithoutKmTodayQuery = Bus::whereDoesntHave('dailyKmRecords', function ($query) use ($today) {
            $query->whereDate('date', $today);
        });

        $busesWithoutKmTodayCount = $busesWithoutKmTodayQuery->count();
        $busesWithoutKmToday = (clone $busesWithoutKmTodayQuery)->limit(10)->get();

        // ─── Transfer notifications ───
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
            'oilAlerts',
        ));
    }

    /**
     * Compute both oil-change statistics and the alert list in a single
     * pass over the active bus list.
     *
     * MEMORY NOTE
     * -----------
     * The buses are eager-loaded with three dedicated "latest"
     * relations — one per oil type — instead of the full `oilChanges`
     * history. This keeps memory usage flat as the fleet grows:
     *
     *   - Before: `with('oilChanges')` → N buses × M history rows.
     *   - After:  three `latestOfMany` relations → N buses × 3 models.
     *
     * The OilChangeStatusService::forBus() call transparently picks
     * up whichever relation is loaded — see resolveLastChange() for
     * the priority chain.
     *
     * @return array{0: array<string,int>, 1: Collection<int, array{bus: Bus, statuses: Collection, min_remaining: int}>}
     */
    private function computeOilChangeData(int $alertLimit = 15): array
    {
        $stats = [
            'overdue' => 0,
            'critical' => 0,
            'due-soon' => 0,
            'ok' => 0,
            'no-history' => 0,
            'total_attention' => 0,
        ];

        // ONE eager-loaded query for the whole request. See the
        // method docblock for why we load the three "latest" relations
        // instead of the full `oilChanges` history.
        $buses = Bus::with([
            'latestMotorOilChange',
            'latestGearboxOilChange',
            'latestAxleOilChange',
            'latestKmRecord',
        ])
            ->where('is_active', true)
            ->get();

        if ($buses->isEmpty()) {
            return [$stats, collect()];
        }

        $service = app(OilChangeStatusService::class);
        $alerts = collect();

        foreach ($buses as $bus) {
            $busStatuses = collect();
            $minRemaining = PHP_INT_MAX;

            foreach (OilType::cases() as $type) {
                $status = $service->forBus($bus, $type);

                if (isset($stats[$status->status])) {
                    $stats[$status->status]++;
                }

                if (in_array($status->status, ['overdue', 'critical'], true)) {
                    $busStatuses->push($status);
                    $minRemaining = min($minRemaining, (int) $status->remainingKm);
                }
            }

            if ($busStatuses->isNotEmpty()) {
                $alerts->push([
                    'bus' => $bus,
                    'statuses' => $busStatuses,
                    'min_remaining' => $minRemaining,
                ]);
            }
        }

        $stats['total_attention'] = $stats['overdue']
            + $stats['critical']
            + $stats['due-soon'];

        $alerts = $alerts
            ->sortBy('min_remaining')
            ->take($alertLimit)
            ->values();

        return [$stats, $alerts];
    }
}