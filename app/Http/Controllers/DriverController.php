<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DriversImport;
use App\Exports\DriversExport;

class DriverController extends Controller
{
    public function index()
    {
        // 🔥 DƏYİŞİKLİK: paginate(30) əlavə edildi
        $drivers = Driver::orderBy('kodu')->paginate(30);
        return view('drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kodu' => 'required|unique:drivers,kodu',
            'ad' => 'required|string|max:255',
            'soyad' => 'nullable|string|max:255',
            'telefon' => 'nullable|string|max:50',
            'vezifesi' => 'nullable|string|max:255',
            'qeyd' => 'nullable|string',
        ]);

        Driver::create($validated);
        return redirect()->route('drivers.index')->with('success', 'Sürücü uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $driver = Driver::findOrFail($id);
        return view('drivers.show', compact('driver'));
    }

    public function edit($id)
    {
        $driver = Driver::findOrFail($id);
        return view('drivers.edit', compact('driver'));
    }

    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $validated = $request->validate([
            'kodu' => 'required|unique:drivers,kodu,' . $id,
            'ad' => 'required|string|max:255',
            'soyad' => 'nullable|string|max:255',
            'telefon' => 'nullable|string|max:50',
            'vezifesi' => 'nullable|string|max:255',
            'qeyd' => 'nullable|string',
        ]);

        $driver->update($validated);
        return redirect()->route('drivers.index')->with('success', 'Sürücü uğurla yeniləndi!');
    }

    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();
        return redirect()->route('drivers.index')->with('success', 'Sürücü uğurla silindi!');
    }

    // ==================== IMPORT ====================
    public function importForm()
    {
        return view('drivers.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(new DriversImport, $request->file('file'));
            return redirect()->route('drivers.index')->with('success', 'Sürücülər uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'Sürücü idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }

    // ==================== EXPORT ====================
    public function export()
    {
        return Excel::download(new DriversExport, 'suruculer.xlsx');
    }
}
