<?php

namespace App\Http\Controllers;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusOilChange;
use App\Services\GarageContext;
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
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BusOilChange::class);

        $statusFilter = (string) $request->input('status', 'all');
        $validStatuses = ['all', 'overdue', 'critical', 'due-soon', 'ok', 'no-history'];

        if (! in_array($statusFilter, $validStatuses, true)) {
            $statusFilter = 'all';
        }

        // Aktual tab
        $activeType = OilType::tryFrom((string) $request->input('type', OilType::Motor->value))
            ?? OilType::Motor;

        $rows = $this->buildStatusRows();

        // Hər növ üçün sətir sayı (tab badge-ləri üçün)
        $typeCounts = collect(OilType::cases())->mapWithKeys(function (OilType $t) use ($rows, $statusFilter) {
            $count = $rows->filter(function (array $row) use ($t, $statusFilter) {
                if ($statusFilter === 'all') {
                    return true;
                }
                return $row['statuses'][$t->value]->status === $statusFilter;
            })->count();

            return [$t->value => $count];
        });

        // Aktiv tab üçün sətirlər
        $sectionRows = $rows
            ->filter(function (array $row) use ($activeType, $statusFilter) {
                if ($statusFilter === 'all') {
                    return true;
                }
                return $row['statuses'][$activeType->value]->status === $statusFilter;
            })
            ->sortBy(fn (array $row) => $row['statuses'][$activeType->value]->remainingKm ?? PHP_INT_MAX)
            ->values();

        // Aktiv tab üçün təcili sayı
        $urgentCount = $sectionRows->filter(
            fn (array $row) => in_array($row['statuses'][$activeType->value]->status, ['overdue', 'critical'], true)
        )->count();

        return view('oil-changes.index', [
            'rows'         => $sectionRows,
            'statusFilter' => $statusFilter,
            'activeType'   => $activeType,
            'typeCounts'   => $typeCounts,
            'urgentCount'  => $urgentCount,
        ]);
    }

    /**
     * Urgent-only view: buses with overdue or critical oil changes
     * across any of the three types.
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

        return view('oil-changes.create', [
            'buses'           => $buses,
            'selectedBusId'   => $request->integer('bus_id') ?: null,
            'selectedOilType' => $request->input('type', OilType::Motor->value),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', BusOilChange::class);

        $validated = app(\App\Http\Requests\OilChangeStoreRequest::class)->rules();
        $data = $request->validate($validated);

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
            'buses'  => $buses,
        ]);
    }

    public function update(Request $request, BusOilChange $oilChange): RedirectResponse
    {
        $this->authorize('update', $oilChange);

        $data = $request->validate(
            app(\App\Http\Requests\OilChangeUpdateRequest::class)->rules()
        );

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
     * @return \Illuminate\Support\Collection<int, array{bus: Bus, statuses: Collection, worst_priority: int, min_remaining: int}>
     */
    private function buildStatusRows(): Collection
    {
        $buses = Bus::with([
            'latestKmRecord',
            'oilChanges' => fn ($q) => $q->orderByDesc('actual_km'),
        ])
            ->where('is_active', true)
            ->orderBy('dqn')
            ->get();

        $priority = [
            'no-history' => 5,
            'ok'         => 4,
            'due-soon'   => 3,
            'critical'   => 2,
            'overdue'    => 1,
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
