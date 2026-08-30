<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseStoreRequest;
use App\Http\Requests\WarehouseUpdateRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\WarehouseImport;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $warehouses = Warehouse::when($search, function ($query, $search) {
            return $query->where('kod', 'ILIKE', "%{$search}%")
                        ->orWhere('ad', 'ILIKE', "%{$search}%");
        })
        ->orderBy('id', 'desc')
        ->paginate(config('settings.pagination', 15));

        return view('warehouses.index', compact('warehouses', 'search'));
    }

    public function search(Request $request)
    {
        $search = $request->search;

        $warehouses = Warehouse::when($search, function ($query, $search) {
            return $query->where('kod', 'ILIKE', "%{$search}%")
                        ->orWhere('ad', 'ILIKE', "%{$search}%");
        })
        ->orderBy('id', 'desc')
        ->paginate(config('settings.pagination', 15));

        return view('warehouses.partials.table', compact('warehouses', 'search'));
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(WarehouseStoreRequest $request)
    {
        Warehouse::create($request->validated());
        return redirect()->route('warehouses.index')->with('success', 'Anbar məlumatı uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        return view('warehouses.show', compact('warehouse'));
    }

    public function edit($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(WarehouseUpdateRequest $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->validated());
        return redirect()->route('warehouses.index')->with('success', 'Anbar məlumatı uğurla yeniləndi!');
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'Anbar məlumatı uğurla silindi!');
    }

    // =============== IMPORT ===============

    public function importForm()
    {
        return view('warehouses.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            Excel::import(new WarehouseImport, $request->file('file'));
            return redirect()->route('warehouses.index')->with('success', 'Anbar məlumatları uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('warehouses.index')->with('error', 'İdxal zamanı xəta baş verdi. Faylın formatını yoxlayın və yenidən cəhd edin.');
        }
    }
}
