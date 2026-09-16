<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseStoreRequest;
use App\Http\Requests\WarehouseUpdateRequest;
use App\Imports\WarehouseImport;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Warehouse::class);

        $search = $request->search;
        $view   = $request->input('view', 'active');

        $query = Warehouse::query();

        // Quarantine is a separate view — never shown alongside
        // active stock, because the two are conceptually different
        // (usable vs defective).
        if ($view === 'quarantine') {
            $query->quarantine();
        } elseif ($view === 'all') {
            // no filter
        } else {
            $query->activeStock();
        }

        $warehouses = $this->applySearch($query, $search)
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        $quarantineCount = Warehouse::query()->quarantine()->count();

        return view('warehouses.index', compact('warehouses', 'search', 'view', 'quarantineCount'));
    }

    public function search(Request $request): View
    {
        $this->authorize('viewAny', Warehouse::class);

        $search = $request->search;
        $view   = $request->input('view', 'active');

        $query = Warehouse::query();

        if ($view === 'quarantine') {
            $query->quarantine();
        } elseif ($view !== 'all') {
            $query->activeStock();
        }

        $warehouses = $this->applySearch($query, $search)
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('warehouses.partials.table', compact('warehouses', 'search', 'view'));
    }

    public function create(): View
    {
        $this->authorize('create', Warehouse::class);

        return view('warehouses.create');
    }

    public function store(WarehouseStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Warehouse::class);

        Warehouse::create($request->validated());

        return redirect()->route('warehouses.index')
            ->with('success', __('messages.flash.created', ['Item' => 'Warehouse item']));
    }

    public function show(int $id): View
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->authorize('view', $warehouse);

        return view('warehouses.show', compact('warehouse'));
    }

    public function edit(int $id): View
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->authorize('update', $warehouse);

        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(WarehouseUpdateRequest $request, int $id): RedirectResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return redirect()->route('warehouses.index')
            ->with('success', __('messages.flash.updated', ['Item' => 'Warehouse item']));
    }

    public function destroy(int $id): RedirectResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->authorize('delete', $warehouse);

        $warehouse->delete();

        return redirect()->route('warehouses.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'Warehouse item']));
    }

    public function importForm(): View
    {
        $this->authorize('import', Warehouse::class);

        return view('warehouses.import');
    }

    public function import(Request $request): RedirectResponse
    {
        // Garage context must be resolved BEFORE authorization — see
        // ComplaintController::import() for the full rationale.
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', Warehouse::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(
                new WarehouseImport(
                    $garageId,
                    GarageContext::resolveCompanyId(),
                ),
                $request->file('file')
            );

            return redirect()->route('warehouses.index')
                ->with('success', __('messages.flash.import_success', [
                    'count' => '',
                    'items' => 'Warehouse items',
                ]));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('warehouses.index')
                ->with('error', __('messages.flash.import_error'));
        }
    }

    /**
     * Apply search to query.
     *
     * CRITICAL: `orWhere` must be inside a closure. Otherwise the garage
     * global scope is lost due to SQL operator precedence, and other
     * garages' data leaks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Warehouse>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Warehouse>
     */
    private function applySearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('code', 'ILIKE', "%{$search}%")
                ->orWhere('name', 'ILIKE', "%{$search}%");
        });
    }
}
