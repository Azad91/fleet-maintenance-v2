<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseStoreRequest;
use App\Http\Requests\WarehouseUpdateRequest;
use App\Imports\WarehouseImport;
use App\Models\Warehouse;
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
        $warehouses = $this->applySearch(Warehouse::query(), $search)
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('warehouses.index', compact('warehouses', 'search'));
    }

    public function search(Request $request): View
    {
        $this->authorize('viewAny', Warehouse::class);

        $search = $request->search;
        $warehouses = $this->applySearch(Warehouse::query(), $search)
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('warehouses.partials.table', compact('warehouses', 'search'));
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
            ->with('success', 'Anbar məlumatı uğurla əlavə edildi!');
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
            ->with('success', 'Anbar məlumatı uğurla yeniləndi!');
    }

    public function destroy(int $id): RedirectResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->authorize('delete', $warehouse);

        $warehouse->delete();

        return redirect()->route('warehouses.index')
            ->with('success', 'Anbar məlumatı uğurla silindi!');
    }

    public function importForm(): View
    {
        $this->authorize('import', Warehouse::class);

        return view('warehouses.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('import', Warehouse::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(
                new WarehouseImport(
                    (int) session('current_garage_id'),
                    session('current_company_id') ? (int) session('current_company_id') : null
                ),
                $request->file('file')
            );

            return redirect()->route('warehouses.index')
                ->with('success', 'Anbar məlumatları uğurla idxal edildi!');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('warehouses.index')
                ->with('error', 'İdxal zamanı xəta baş verdi. Faylın formatını yoxlayın və yenidən cəhd edin.');
        }
    }

    /**
     * Axtarış sərtini query-ə tətbiq edir.
     *
     * ✅ KRİTİK: `orWhere` mütləq closure içində olmalıdır.
     * Əks halda HasGarageScope ilə birləşərkən SQL operator prioriteti
     * səbəbindən qaraj scope-u itir və başqa qarajların məlumatları sızır.
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
