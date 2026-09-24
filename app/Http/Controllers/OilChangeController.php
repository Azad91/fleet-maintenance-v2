<?php

namespace App\Http\Controllers;

use App\Enums\OilType;
use App\Http\Requests\OilChangeStoreRequest;
use App\Http\Requests\OilChangeUpdateRequest;
use App\Models\Bus;
use App\Models\BusOilChange;
use App\Services\OilChange\OilChangeService;
use App\Services\OilChange\OilChangeStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OilChangeController extends Controller
{
    public function __construct(
        protected OilChangeService $service,
        protected OilChangeStatusService $statusService,
    ) {}

    /**
     * Combined view: one section per oil type.
     *
     * Delegates filter resolution and data collection to
     * collectFilteredData() so that both the initial page load
     * (index) and the AJAX search endpoint share the exact same
     * filtering rules.
     */
    public function index(Request $request): View
    {
        return view('oil-changes.index', $this->collectFilteredData($request));
    }

    /**
     * AJAX search endpoint. Returns only the results partial when
     * called via XMLHttpRequest, and falls back to the full index
     * view for direct URL visits or middle-click navigation.
     *
     * Mirrors the BusController::search() and WarehouseController
     * ::search() pattern.
     */
    public function search(Request $request): View|string
    {
        $data = $this->collectFilteredData($request);

        if ($this->isAjaxRequest($request)) {
            return view('oil-changes.partials.results', $data)->render();
        }

        return view('oil-changes.index', $data);
    }

    /**
     * Resolve every filter from the request, build the full row set,
     * then apply status and KM filters.
     *
     * ── FILTER LAYERS ──────────────────────────────────────────────
     * 1. DB-level (in buildStatusRows):
     *      - dqn          → buses.dqn ILIKE
     *      - route_number → buses.route_number ILIKE
     *
     * 2. Collection-level (here):
     *      - km_min / km_max → current km of the active oil type
     *      - status          → worst-match status of the active type
     *
     * KM and status filters run in PHP because both depend on
     * computed values (OilChangeStatus), not on raw columns.
     */
    private function collectFilteredData(Request $request): array
    {
        $this->authorize('viewAny', BusOilChange::class);

        $statusFilter = (string) $request->input('status', 'all');
        $validStatuses = ['all', 'overdue', 'critical', 'due-soon', 'ok', 'no-history'];

        if (! in_array($statusFilter, $validStatuses, true)) {
            $statusFilter = 'all';
        }

        $activeType = OilType::tryFrom((string) $request->input('type', OilType::Motor->value))
            ?? OilType::Motor;

        $filters = [
            'dqn' => trim((string) $request->input('dqn', '')),
            'route_number' => trim((string) $request->input('route_number', '')),
            'km_min' => $request->filled('km_min') ? max(0, (int) $request->input('km_min')) : null,
            'km_max' => $request->filled('km_max') ? max(0, (int) $request->input('km_max')) : null,
        ];

        $rows = $this->buildStatusRows($filters);

        // ─── KM range filter (on the active oil type's current km) ───
        if ($filters['km_min'] !== null || $filters['km_max'] !== null) {
            $rows = $rows->filter(function (array $row) use ($activeType, $filters) {
                $currentKm = $row['statuses'][$activeType->value]->currentKm ?? 0;

                if ($filters['km_min'] !== null && $currentKm < $filters['km_min']) {
                    return false;
                }
                if ($filters['km_max'] !== null && $currentKm > $filters['km_max']) {
                    return false;
                }

                return true;
            })->values();
        }

        $typeCounts = collect(OilType::cases())->mapWithKeys(function (OilType $t) use ($rows, $statusFilter) {
            $count = $rows->filter(function (array $row) use ($t, $statusFilter) {
                if ($statusFilter === 'all') {
                    return true;
                }

                return $row['statuses'][$t->value]->status === $statusFilter;
            })->count();

            return [$t->value => $count];
        });

        $sectionRows = $rows
            ->filter(function (array $row) use ($activeType, $statusFilter) {
                if ($statusFilter === 'all') {
                    return true;
                }

                return $row['statuses'][$activeType->value]->status === $statusFilter;
            })
            ->sortBy(fn (array $row) => $row['statuses'][$activeType->value]->remainingKm ?? PHP_INT_MAX)
            ->values();

        $urgentCount = $sectionRows->filter(
            fn (array $row) => in_array($row['statuses'][$activeType->value]->status, ['overdue', 'critical'], true)
        )->count();

        return [
            'rows' => $sectionRows,
            'statusFilter' => $statusFilter,
            'activeType' => $activeType,
            'typeCounts' => $typeCounts,
            'urgentCount' => $urgentCount,
            'filters' => $filters,
        ];
    }

    /**
     * True when the request should receive a partial view instead of
     * the full page. Same detection pattern used across the codebase.
     */
    private function isAjaxRequest(Request $request): bool
    {
        return $request->header('X-Requested-With') === 'XMLHttpRequest'
            || $request->boolean('_ajax');
    }

    /**
     * Urgent-only view.
     */
    public function urgent(): View
    {
        $this->authorize('viewAny', BusOilChange::class);

        $rows = $this->buildStatusRows()
            ->map(function (array $row) {
                $row['urgent_statuses'] = $row['statuses']
                    ->filter(fn ($s) => in_array($s->status, ['overdue', 'critical'], true));

                return $row;
            })
            ->filter(fn (array $row) => $row['urgent_statuses']->isNotEmpty())
            ->sortBy('min_remaining')
            ->values();

        $overdueCount = $rows->sum(
            fn (array $r) => $r['urgent_statuses']->where('status', 'overdue')->count()
        );

        $criticalCount = $rows->sum(
            fn (array $r) => $r['urgent_statuses']->where('status', 'critical')->count()
        );

        return view('oil-changes.urgent', [
            'rows' => $rows,
            'overdueCount' => $overdueCount,
            'criticalCount' => $criticalCount,
        ]);
    }

    public function show(Bus $bus): View
    {
        $this->authorize('viewAny', BusOilChange::class);

        $bus->load([
            'latestKmRecord',
            'oilChanges' => fn ($q) => $q->orderByDesc('oil_type')->orderByDesc('actual_km'),
        ]);

        $statuses = collect(OilType::cases())
            ->mapWithKeys(fn (OilType $t) => [$t->value => $this->statusService->forBus($bus, $t)]);

        return view('oil-changes.show', compact('bus', 'statuses'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', BusOilChange::class);

        $buses = Bus::active()->orderBy('dqn')->get();

        $selectedBusId = $request->integer('bus_id') ?: null;

        $selectedOilType = OilType::tryFrom((string) $request->input('type', OilType::Motor->value))
            ?? OilType::Motor;

        // ✅ Pre-fill the "Scheduled km" field with the catalog
        // milestone shown on the index page ("YAĞDƏYİŞMƏ NÖVÜ" column).
        //
        // Priority:
        //   1. nextCatalogKm — the exact value the operator saw on
        //      the index page (a real catalog milestone, tied to a
        //      parts list).
        //   2. nextDueKm     — the mathematically computed next due
        //      (used only when the catalog has no milestone beyond
        //      the current due point — e.g. a bus already past the
        //      last catalog entry).
        //   3. null          — no bus selected yet; field stays empty.
        $suggestedScheduledKm = null;

        if ($selectedBusId) {
            $bus = Bus::with(['latestKmRecord', 'oilChanges'])
                ->find($selectedBusId);

            if ($bus) {
                $status = $this->statusService->forBus($bus, $selectedOilType);

                $suggestedScheduledKm = $status->nextCatalogKm
                    ?? $status->nextDueKm;
            }
        }

        return view('oil-changes.create', [
            'buses' => $buses,
            'selectedBusId' => $selectedBusId,
            'selectedOilType' => $selectedOilType->value,
            'suggestedScheduledKm' => $suggestedScheduledKm,
        ]);
    }

    /**
     * ✅ P1 FIX: FormRequest injection.
     *
     * ƏVVƏL:
     *   public function store(Request $request) {
     *       $validated = app(OilChangeStoreRequest::class)->rules();
     *       $data = $request->validate($validated);
     *   }
     *
     * Bu pattern prepareForValidation()-u və Rule::requiredIf() closure-unu
     * sındırırdı, çünki Laravel FormRequest lifecycle-ı tam işlədilmirdi.
     *
     * İNDİ: Tip-hint ilə FormRequest inject olunur → Laravel hər şeyi
     * (authorize, prepareForValidation, rules, messages) düzgün işə salır.
     */
    public function store(OilChangeStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', BusOilChange::class);

        $data = $request->validated();

        $bus = Bus::findOrFail($data['bus_id']);

        $this->service->create($bus, $data);

        return redirect()
            ->route('oil-changes.show', $bus)
            ->with('success', __('messages.flash.created', ['Item' => 'Oil change']));
    }

    public function edit(BusOilChange $oilChange): View
    {
        $this->authorize('update', $oilChange);

        $buses = Bus::active()->orderBy('dqn')->get();

        return view('oil-changes.edit', [
            'change' => $oilChange,
            'buses' => $buses,
        ]);
    }

    /**
     * ✅ P1 FIX: FormRequest injection (update üçün də eyni problem idi).
     */
    public function update(OilChangeUpdateRequest $request, BusOilChange $oilChange): RedirectResponse
    {
        $this->authorize('update', $oilChange);

        $data = $request->validated();

        $this->service->update($oilChange, $data);

        return redirect()
            ->route('oil-changes.show', $oilChange->bus_id)
            ->with('success', __('messages.flash.updated', ['Item' => 'Oil change']));
    }

    public function destroy(BusOilChange $oilChange): RedirectResponse
    {
        $this->authorize('delete', $oilChange);

        $busId = $oilChange->bus_id;
        $this->service->delete($oilChange);

        return redirect()
            ->route('oil-changes.show', $busId)
            ->with('success', __('messages.flash.deleted', ['Item' => 'Oil change']));
    }

    /**
     * Build one row per active bus, with all three oil-type statuses.
     *
     * MEMORY NOTE
     * -----------
     * The `with([...])` block loads the three dedicated "latest"
     * relations (one per oil type) instead of the full `oilChanges`
     * history. Each `latestOfMany('actual_km')` relation costs one
     * query for the whole collection, so we pay 3 queries total
     * regardless of fleet size or history depth.
     *
     * The previous `with('oilChanges')` approach loaded every
     * historical change for every bus — see Bus::latestMotorOilChange()
     * for the full rationale.
     *
     * `OilChangeStatusService::forBus()` transparently picks up
     * whichever relation is loaded via resolveLastChange().
     *
     * @param  array{dqn?: string, route_number?: string}  $filters
     *                                                               DQN and route filters are applied at the DB level so
     *                                                               PostgreSQL does the work before Laravel collects rows.
     *                                                               KM and status filters are applied later in PHP because
     *                                                               they depend on computed OilChangeStatus values.
     */
    private function buildStatusRows(array $filters = []): Collection
    {
        $buses = Bus::with([
            'latestKmRecord',
            // ✅ Eager-load the latest daily status so the index page
            // can render it in a dedicated column without an N+1.
            // `latestDailyStatus()` is defined on the Bus model using
            // latestOfMany('date') — a single subquery for the whole
            // collection.
            'latestDailyStatus',
            // ✅ Latest oil change per type — three relations total,
            // NOT the full history. See the method docblock.
            'latestMotorOilChange',
            'latestGearboxOilChange',
            'latestAxleOilChange',
        ])
            ->where('is_active', true)
            ->when(
                ! empty($filters['dqn']),
                fn ($q) => $q->where('dqn', 'ILIKE', '%'.$filters['dqn'].'%')
            )
            ->when(
                ! empty($filters['route_number']),
                fn ($q) => $q->where('route_number', 'ILIKE', '%'.$filters['route_number'].'%')
            )
            ->orderBy('dqn')
            ->get();

        $priority = [
            'no-history' => 5,
            'ok' => 4,
            'due-soon' => 3,
            'critical' => 2,
            'overdue' => 1,
        ];

        return $buses->map(function (Bus $bus) use ($priority) {
            $statuses = collect(OilType::cases())
                ->mapWithKeys(fn (OilType $t) => [$t->value => $this->statusService->forBus($bus, $t)]);

            $worstPriority = collect($statuses->values())
                ->map(fn ($s) => $priority[$s->status] ?? 99)
                ->min();

            $minRemaining = collect($statuses->values())
                ->map(fn ($s) => $s->remainingKm)
                ->filter(fn ($v) => $v !== null)
                ->min() ?? PHP_INT_MAX;

            return [
                'bus' => $bus,
                'statuses' => $statuses,
                'worst_priority' => $worstPriority,
                'min_remaining' => $minRemaining,
            ];
        });
    }
}
