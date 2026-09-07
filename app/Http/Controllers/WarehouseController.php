<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseStoreRequest;
use App\Http\Requests\WarehouseUpdateRequest;
use App\Imports\WarehouseImport;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Warehouse::class);  // ✅ ƏLAVƏ

        $search = $request->search;

        $warehouses = Warehouse::when($search, function ($query, $search) {
            return $query->where('code', 'ILIKE', "%{$search}%")
                ->orWhere('name', 'ILIKE', "%{$search}%");
        })
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('warehouses.index', compact('warehouses', 'search'));
    }

    public function search(Request $request)
    {
        $this->authorize('viewAny', Warehouse::class);  // ✅ ƏLAVƏ

        $search = $request->search;

        $warehouses = Warehouse::when($search, function ($query, $search) {
            return $query->where('code', 'ILIKE', "%{$search}%")
                ->orWhere('name', 'ILIKE', "%{$search}%");
        })
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('warehouses.partials.table', compact('warehouses', 'search'));
    }

    public function create()
    {
        $this->authorize('create', Warehouse::class);  // ✅ ƏLAVƏ

        return view('warehouses.create');
    }

    public function store(WarehouseStoreRequest $request)
    {
        $this->authorize('create', Warehouse::class);  // ✅ ƏLAVƏ

        Warehouse::create($request->validated());

        return redirect()->route('warehouses.index')->with('success', 'Anbar məlumatı uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->authorize('view', $warehouse);  // ✅ ƏLAVƏ

        return view('warehouses.show', compact('warehouse'));
    }

    public function edit($id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->authorize('update', $warehouse);  // ✅ ƏLAVƏ

        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(WarehouseUpdateRequest $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->authorize('update', $warehouse);  // ✅ ƏLAVƏ

        $warehouse->update($request->validated());

        return redirect()->route('warehouses.index')->with('success', 'Anbar məlumatı uğurla yeniləndi!');
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->authorize('delete', $warehouse);  // ✅ ƏLAVƏ

        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Anbar məlumatı uğurla silindi!');
    }

    public function importForm()
    {
        $this->authorize('import', Warehouse::class);  // ✅ ƏLAVƏ

        return view('warehouses.import');
    }

    public function import(Request $request)
    {
        $this->authorize('import', Warehouse::class);  // ✅ ƏLAVƏ

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

            return redirect()->route('warehouses.index')->with('success', 'Anbar məlumatları uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('warehouses.index')->with('error', 'İdxal zamanı xəta baş verdi. Faylın formatını yoxlayın və yenidən cəhd edin.');
        }
    }
}
