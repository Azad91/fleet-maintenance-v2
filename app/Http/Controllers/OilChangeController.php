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
use Illuminate\View\View;

class OilChangeController extends Controller
{
    public function __construct(
        protected OilChangeService $service,
        protected OilChangeStatusService $statusService,
    ) {}

    /**
     * Priority list — the landing page.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BusOilChange::class);

        // Status filter: all | overdue | critical | due-soon | ok | no-history
        $statusFilter = (string) $request->input('status', 'all');
        $validStatuses = ['all', 'overdue', 'critical', 'due-soon', 'ok', 'no-history'];

        if (! in_array($statusFilter, $validStatuses, true)) {
            $statusFilter = 'all';
        }

        // Eager-load all oil changes (all types) and latest km record.
        $buses = Bus::with([
            'latestKmRecord',
            'oilChanges' => fn ($q) => $q->orderByDesc('actual_km'),
        ])
            ->where('is_active', true)
            ->orderBy('dqn')
            ->get();

        // Build one row per bus, with all three oil type statuses.
        $rows = $buses->map(function (Bus $bus) {
            $statuses = collect(OilType::cases())
                ->mapWithKeys(fn (OilType $t) => [$t->value => $this->statusService->forBus($bus, $t)]);

            // Determine the "worst" status across all three types to sort by.
            $priority = [
                'no-history' => 5,
                'ok'         => 4,
                'due-soon'   => 3,
                'critical'   => 2,
                'overdue'    => 1,
            ];

            $worstPriority = collect($statuses->values())
                ->map(fn ($s) => $priority[$s->status] ?? 99)
                ->min();

            // For sorting: use the smallest remaining km across types
            // (overdue rows are negative, so they float to the top).
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

        // Apply status filter: keep a bus if ANY of its three oil types
        // matches the requested status.
        if ($statusFilter !== 'all') {
            $rows = $rows->filter(
                fn ($row) => $row['statuses']->contains(fn ($s) => $s->status === $statusFilter)
            );
        }

        // Sort: most urgent first, then by DQN.
        $rows = $rows
            ->sortBy([
                ['worst_priority', 'asc'],
                ['min_remaining', 'asc'],
            ])
            ->values();

        return view('oil-changes.index', [
            'rows' => $rows,
            'statusFilter' => $statusFilter,
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
}
