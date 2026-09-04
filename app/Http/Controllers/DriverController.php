<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DriversImport;
use App\Exports\DriversExport;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    public function index()
    {
        // 🔥 DƏYİŞİKLİK: paginate(30) əlavə edildi
        $drivers = Driver::orderBy('kodu')->paginate(config('settings.pagination'));
        return view('drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'kodu' => mb_strtoupper(trim((string) $request->input('kodu'))),
            'ad' => trim((string) $request->input('ad')),
            'soyad' => $request->filled('soyad') ? trim((string) $request->input('soyad')) : null,
        ]);

        $validated = $request->validate([
            'kodu' => [
                'required', 'string', 'max:100',
                Rule::unique('drivers', 'kodu')->where(fn ($query) => $query
                    ->where('garage_id', \App\Models\Garage::getCurrentId())
                    ->whereNull('deleted_at')),
            ],
            'ad' => 'required|string|max:255',
            'soyad' => 'nullable|string|max:255',
            'telefon' => 'nullable|string|max:50',
            'vezifesi' => 'nullable|string|max:255',
            'aktiv' => 'required|boolean',
            'qeyd' => 'nullable|string',
        ], [
            'kodu.unique' => 'Bu sürücü kodu seçilmiş qarajda artıq mövcuddur.',
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

        $request->merge([
            'kodu' => mb_strtoupper(trim((string) $request->input('kodu'))),
            'ad' => trim((string) $request->input('ad')),
            'soyad' => $request->filled('soyad') ? trim((string) $request->input('soyad')) : null,
        ]);

        $validated = $request->validate([
            'kodu' => [
                'required', 'string', 'max:100',
                Rule::unique('drivers', 'kodu')->ignore($driver->id)->where(fn ($query) => $query
                    ->where('garage_id', \App\Models\Garage::getCurrentId())
                    ->whereNull('deleted_at')),
            ],
            'ad' => 'required|string|max:255',
            'soyad' => 'nullable|string|max:255',
            'telefon' => 'nullable|string|max:50',
            'vezifesi' => 'nullable|string|max:255',
            'aktiv' => 'required|boolean',
            'qeyd' => 'nullable|string',
        ], [
            'kodu.unique' => 'Bu sürücü kodu seçilmiş qarajda artıq mövcuddur.',
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
