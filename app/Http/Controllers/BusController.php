<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusStoreRequest;
use App\Http\Requests\BusUpdateRequest;
use App\Imports\BusesImport;
use App\Models\Bus;
use App\Services\BusService;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class BusController extends Controller
{
    public function __construct(protected BusService $busService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Bus::class);

        $buses = $this->busService->getPaginatedBuses(null, config('settings.pagination', 15));

        return view('buses.index', [
            'buses' => $buses,
            'isEmpty' => $buses->isEmpty(),
            'hasActiveFilters' => false,
        ]);
    }

    public function search(Request $request): View|string
    {
        $this->authorize('viewAny', Bus::class);

        $filters = $request->only([
            'bus_project', 'vin', 'uzunluq', 'route_number', 'dqn', 'engine_number',
        ]);

        $filters = array_filter($filters, fn ($v) => filled($v));

        $buses = $this->busService->advancedSearch(
            $filters,
            (int) config('settings.pagination', 15)
        );

        $isEmpty = $buses->isEmpty();
        $hasActiveFilters = ! empty($filters);

        if ($this->isAjaxRequest($request)) {
            return view('buses.partials.table', compact('buses', 'isEmpty', 'hasActiveFilters'))->render();
        }

        return view('buses.index', compact('buses', 'isEmpty', 'hasActiveFilters'));
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->header('X-Requested-With') === 'XMLHttpRequest'
            || $request->boolean('_ajax');
    }

    public function show(int $id): View
    {
        $bus = Bus::findOrFail($id);
        $this->authorize('view', $bus);

        return view('buses.show', compact('bus'));
    }

    public function create(): View
    {
        $this->authorize('create', Bus::class);

        return view('buses.create');
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
        $bus = Bus::findOrFail($id);
        $this->authorize('update', $bus);

        return view('buses.edit', compact('bus'));
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

        return view('buses.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('import', Bus::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new BusesImport(
                (int) GarageContext::getGarageId(),
                GarageContext::getCompanyId() ? (int) GarageContext::getCompanyId() : null
            );

            Excel::import($import, $request->file('file'));

            $skipped  = $import->skipped;
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
            ->with('success', __('messages.flash.' . $key, [
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
     * Normalize an array of IDs (handles JSON string input from forms).
     *
     * @return array<int>
     */
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
