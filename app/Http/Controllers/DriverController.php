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
        $drivers = Driver::orderBy('code')->paginate(config('settings.pagination', 30));
        return view('drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'code' => mb_strtoupper(trim((string) $request->input('code'))),
            'first_name' => trim((string) $request->input('first_name')),
            'last_name' => $request->filled('last_name') ? trim((string) $request->input('last_name')) : null,
        ]);

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('drivers', 'code')->where(fn ($query) => $query
                    ->where('garage_id', session('current_garage_id'))
                    ->whereNull('deleted_at')),
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string',
        ], [
            'code.unique' => 'Bu sürücü kodu seçilmiş qarajda artıq mövcuddur.',
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
            'code' => mb_strtoupper(trim((string) $request->input('code'))),
            'first_name' => trim((string) $request->input('first_name')),
            'last_name' => $request->filled('last_name') ? trim((string) $request->input('last_name')) : null,
        ]);

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('drivers', 'code')->ignore($driver->id)->where(fn ($query) => $query
                    ->where('garage_id', session('current_garage_id'))
                    ->whereNull('deleted_at')),
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string',
        ], [
            'code.unique' => 'Bu sürücü kodu seçilmiş qarajda artıq mövcuddur.',
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

    public function export()
    {
        return Excel::download(new DriversExport, 'suruculer.xlsx');
    }
}
