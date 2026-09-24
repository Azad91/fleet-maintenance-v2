<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusStoreRequest;
use App\Http\Requests\BusUpdateRequest;
use App\Imports\BusesImport;
use App\Models\Bus;
use App\Models\BusBrand;
use App\Services\BusService;
use App\Services\GarageContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class BusController extends Controller
{
    public function __construct(protected BusService $busService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Bus::class);

        $buses = $this->busService->getPaginatedBuses(null, config('settings.pagination', 15));

        $brands = BusBrand::active()->orderBy('name')->get();

        return view('buses.index', [
            'buses' => $buses,
            'brands' => $brands,
            'isEmpty' => $buses->isEmpty(),
            'hasActiveFilters' => false,
        ]);
    }

    public function search(Request $request): View|string
    {
        $this->authorize('viewAny', Bus::class);

        $filters = $request->only([
            'brand_id', 'bus_project', 'vin', 'uzunluq', 'route_number', 'dqn', 'engine_number',
        ]);

        $filters = array_filter($filters, fn ($v) => filled($v));

        $buses = $this->busService->advancedSearch(
            $filters,
            (int) config('settings.pagination', 15)
        );

        $brands = BusBrand::active()->orderBy('name')->get();

        $isEmpty = $buses->isEmpty();
        $hasActiveFilters = ! empty($filters);

        if ($this->isAjaxRequest($request)) {
            return view('buses.partials.table', compact('buses', 'brands', 'isEmpty', 'hasActiveFilters'))->render();
        }

        return view('buses.index', compact('buses', 'brands', 'isEmpty', 'hasActiveFilters'));
    }

    public function show(Request $request, int $id): View
    {
        $bus = Bus::with(['brand', 'latestDailyStatus'])->findOrFail($id);
        $this->authorize('view', $bus);

        // ─── KM records (paginated) ───
        $kmRecords = $bus->dailyKmRecords()
            ->paginate(30, ['*'], 'km_page')
            ->withQueryString();

        $kmItems = $kmRecords->items();
        $oldestOnPage = ! empty($kmItems) ? $kmItems[count($kmItems) - 1] : null;
        $previousOfOldest = $oldestOnPage
            ? $bus->dailyKmRecords()->where('date', '<', $oldestOnPage->date)->first()
            : null;

        foreach ($kmItems as $index => $record) {
            $previous = $kmItems[$index + 1] ?? $previousOfOldest;
            $record->daily_km = $previous !== null
                ? max(0, $record->km - $previous->km)
                : null;
        }
        $kmRecords->setCollection(collect($kmItems));

        // ─── Status records — filtered by month ───
        $statusMonth = $request->input('status_month');

        if (! is_string($statusMonth) || ! preg_match('/^\d{4}-\d{2}$/', $statusMonth)) {
            $statusMonth = now()->format('Y-m');
        }

        try {
            $monthStart = Carbon::parse($statusMonth.'-01')->startOfMonth();
        } catch (\Throwable $e) {
            $statusMonth = now()->format('Y-m');
            $monthStart = now()->startOfMonth();
        }

        $monthEnd = $monthStart->copy()->endOfMonth();

        $statusRecords = $bus->dailyStatuses()
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->paginate(31, ['*'], 'status_page')
            ->withQueryString();

        $monthlySummary = $bus->dailyStatuses()
            ->reorder()
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->select('status', DB::raw('COUNT(*) as days'))
            ->groupBy('status')
            ->orderByDesc('days')
            ->get();

        return view('buses.show', compact(
            'bus',
            'kmRecords',
            'statusRecords',
            'statusMonth',
            'monthStart',
            'monthEnd',
            'monthlySummary',
        ));
    }

    public function create(): View
    {
        $this->authorize('create', Bus::class);

        $brands = BusBrand::active()->orderBy('name')->get();

        return view('buses.create', compact('brands'));
    }

    public function store(BusStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Bus::class);

        $this->busService->createBus($request->validated());

        return redirect()->route('buses.index')
            ->with('success', __('messages.flash.created', ['Item' => 'Bus']));
    }

    public function edit(int $id): View
    {
        $bus = Bus::with('brand')->findOrFail($id);
        $this->authorize('update', $bus);

        $brands = BusBrand::active()->orderBy('name')->get();

        return view('buses.edit', compact('bus', 'brands'));
    }

    public function update(BusUpdateRequest $request, int $id): RedirectResponse
    {
        $bus = Bus::findOrFail($id);
        $this->authorize('update', $bus);

        $this->busService->updateBus($bus, $request->validated());

        return redirect()->route('buses.index')
            ->with('success', __('messages.flash.updated', ['Item' => 'Bus']));
    }

    public function destroy(int $id): RedirectResponse
    {
        $bus = Bus::findOrFail($id);
        $this->authorize('delete', $bus);

        $this->busService->deleteBus($bus);

        return redirect()->route('buses.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'Bus']));
    }

    public function importForm(): View
    {
        $this->authorize('import', Bus::class);

        $brands = BusBrand::active()->orderBy('name')->get();

        return view('buses.import', compact('brands'));
    }

    public function import(Request $request): RedirectResponse
    {
        // Garage context must be resolved BEFORE authorization.
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', Bus::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'brand_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('bus_brands', 'id')->where('garage_id', $garageId),
            ],
        ]);

        try {
            $import = new BusesImport(
                $garageId,
                GarageContext::resolveCompanyId(),
                $request->filled('brand_id') ? (int) $request->input('brand_id') : null,
            );

            Excel::import($import, $request->file('file'));

            $skipped = $import->skipped;
            $imported = $import->importedCount;

            if (empty($skipped)) {
                return redirect()->route('buses.index')
                    ->with('success', __('messages.flash.import_success', [
                        'count' => $imported,
                        'items' => 'buses',
                    ]));
            }

            return redirect()->route('buses.index')
                ->with('warning', __('messages.flash.import_partial'))
                ->with('import_report', $this->buildImportReport($imported, $skipped, collect()));

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('buses.index')
                ->with('error', __('messages.flash.import_error'));
        }
    }

    public function bulkDeactivate(Request $request): RedirectResponse
    {
        return $this->bulkUpdateStatus($request, false);
    }

    public function bulkActivate(Request $request): RedirectResponse
    {
        return $this->bulkUpdateStatus($request, true);
    }

    private function bulkUpdateStatus(Request $request, bool $isActive): RedirectResponse
    {
        $this->authorize('update', Bus::class);

        $ids = $this->normalizeIds($request->input('ids', []));

        if (empty($ids)) {
            return back()->with('error', __('messages.flash.none_selected'));
        }

        $this->busService->bulkUpdateStatus($ids, $isActive);

        $key = $isActive ? 'bulk_activated' : 'bulk_deactivated';

        return redirect()->route('buses.index')
            ->with('success', __('messages.flash.'.$key, [
                'count' => count($ids),
                'items' => 'buses',
            ]));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $this->authorize('delete', Bus::class);

        $ids = $this->normalizeIds($request->input('ids', []));

        if (empty($ids)) {
            return back()->with('error', __('messages.flash.none_selected'));
        }

        $this->busService->bulkDelete($ids);

        return redirect()->route('buses.index')
            ->with('success', __('messages.flash.bulk_deleted', [
                'count' => count($ids),
                'items' => 'buses',
            ]));
    }

    /**
     * Bulk soft-delete ALL buses matching the current filter.
     */
    public function bulkDeleteAll(Request $request): RedirectResponse
    {
        $this->authorize('delete', Bus::class);

        @set_time_limit(300);

        $filters = $request->only([
            'brand_id', 'bus_project', 'vin', 'uzunluq', 'route_number', 'dqn', 'engine_number',
        ]);

        $filters = array_filter($filters, fn ($v) => filled($v));

        $count = $this->busService->bulkDeleteAllByFilters($filters);

        if ($count === 0) {
            return redirect()
                ->route('buses.index')
                ->with('error', __('messages.flash.none_selected'));
        }

        return redirect()
            ->route('buses.index')
            ->with('success', __('messages.flash.bulk_deleted', [
                'count' => $count,
                'items' => 'buses',
            ]));
    }

    private function normalizeIds(mixed $ids): array
    {
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $ids)));
    }
}
