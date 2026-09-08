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

        return view('buses.index', compact('buses'));
    }

    public function search(Request $request): View|string
    {
        $this->authorize('viewAny', Bus::class);

        $buses = $this->busService->advancedSearch($request->all(), config('settings.pagination', 15));
        $isEmpty = $buses->isEmpty();

        if ($request->ajax()) {
            return view('buses.partials.table', compact('buses', 'isEmpty'))->render();
        }

        return view('buses.index', compact('buses'));
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
                new BusesImport((int) GarageContext::getGarageId(), GarageContext::getCompanyId() ? (int) GarageContext::getCompanyId() : null),
                $request->file('file')
            );
            return redirect()->route('buses.index')->with('success', 'Avtobuslar uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('buses.index')->with('error', 'İdxal zamanı xəta baş verdi. Faylın formatını yoxlayın.');
        }
    }

    public function bulkDeactivate(Request $request): RedirectResponse
    {
        $this->authorize('update', Bus::class);

        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Heç bir avtobus seçilməyib.');

        $this->busService->bulkUpdateStatus($ids, false);

        return redirect()->route('buses.index')->with('success', count($ids) . ' avtobus passiv edildi.');
    }

    public function bulkActivate(Request $request): RedirectResponse
    {
        $this->authorize('update', Bus::class);

        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Heç bir avtobus seçilməyib.');

        $this->busService->bulkUpdateStatus($ids, true);

        return redirect()->route('buses.index')->with('success', count($ids) . ' avtobus aktiv edildi.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $this->authorize('delete', Bus::class);

        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Heç bir avtobus seçilməyib.');

        $this->busService->bulkDelete($ids);

        return redirect()->route('buses.index')->with('success', count($ids) . ' avtobus silindi.');
    }
}
