<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusStoreRequest;
use App\Http\Requests\BusUpdateRequest;
use App\Imports\BusesImport;
use App\Models\Bus;
use App\Services\BusService;
use App\Services\GarageContext;
use Illuminate\Http\JsonResponse;
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

        // ✅ DÜZƏLİŞ: yalnız filter sahələrini götür
        $filters = $request->only([
            'bus_project', 'vin', 'uzunluq', 'route_number', 'dqn', 'engine_number',
        ]);

        // Boş dəyərləri təmizlə
        $filters = array_filter($filters, fn ($v) => filled($v));

        $buses = $this->busService->advancedSearch(
            $filters,
            (int) config('settings.pagination', 15)
        );

        $isEmpty = $buses->isEmpty();
        $hasActiveFilters = ! empty($filters);

        // ✅ DÜZƏLİŞ: $request->ajax() əvəzinə explicit header yoxlaması
        if ($this->isAjaxRequest($request)) {
            return view('buses.partials.table', compact('buses', 'isEmpty', 'hasActiveFilters'))->render();
        }

        return view('buses.index', compact('buses', 'isEmpty', 'hasActiveFilters'));
    }

    /**
     * Sorğunun AJAX olub-olmadığını yoxlayır.
     *
     * fetch() ilə göndərilən sorğular X-Requested-With header-ini manual set etməlidir.
     * jQuery default olaraq set edir.
     */
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

        return redirect()->route('buses.index')->with('success', 'Avtobus uğurla əlavə edildi!');
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

        return redirect()->route('buses.index')->with('success', 'Avtobus uğurla yeniləndi!');
    }

    public function destroy(int $id): RedirectResponse
    {
        $bus = Bus::findOrFail($id);
        $this->authorize('delete', $bus);

        $this->busService->deleteBus($bus);

        return redirect()->route('buses.index')->with('success', 'Avtobus uğurla silindi!');
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
            Excel::import(
                new BusesImport(
                    (int) GarageContext::getGarageId(),
                    GarageContext::getCompanyId() ? (int) GarageContext::getCompanyId() : null
                ),
                $request->file('file')
            );

            return redirect()->route('buses.index')->with('success', 'Avtobuslar uğurla idxal edildi!');
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('buses.index')->with('error', 'İdxal zamanı xəta baş verdi. Faylın formatını yoxlayın.');
        }
    }

    public function bulkDeactivate(Request $request): RedirectResponse
    {
        return $this->bulkUpdateStatus($request, false, 'passiv edildi');
    }

    public function bulkActivate(Request $request): RedirectResponse
    {
        return $this->bulkUpdateStatus($request, true, 'aktiv edildi');
    }

    private function bulkUpdateStatus(Request $request, bool $isActive, string $label): RedirectResponse
    {
        $this->authorize('update', Bus::class);

        $ids = $this->normalizeIds($request->input('ids', []));

        if (empty($ids)) {
            return back()->with('error', 'Heç bir avtobus seçilməyib.');
        }

        $this->busService->bulkUpdateStatus($ids, $isActive);

        return redirect()->route('buses.index')->with('success', count($ids) . " avtobus {$label}.");
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $this->authorize('delete', Bus::class);

        $ids = $this->normalizeIds($request->input('ids', []));

        if (empty($ids)) {
            return back()->with('error', 'Heç bir avtobus seçilməyib.');
        }

        $this->busService->bulkDelete($ids);

        return redirect()->route('buses.index')->with('success', count($ids) . ' avtobus silindi.');
    }

    /**
     * ID massivini təmizləyir (JSON string və ya array).
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
