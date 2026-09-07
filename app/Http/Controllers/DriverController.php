<?php

namespace App\Http\Controllers;

use App\Exports\DriversExport;
use App\Imports\DriversImport;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class DriverController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Driver::class);  // ✅ ƏLAVƏ

        $drivers = Driver::orderBy('code')->paginate(config('settings.pagination', 30));

        return view('drivers.index', compact('drivers'));
    }

    public function create()
    {
        $this->authorize('create', Driver::class);  // ✅ ƏLAVƏ

        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Driver::class);  // ✅ ƏLAVƏ

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

        $this->authorize('view', $driver);  // ✅ ƏLAVƏ

        return view('drivers.show', compact('driver'));
    }

    public function edit($id)
    {
        $driver = Driver::findOrFail($id);

        $this->authorize('update', $driver);  // ✅ ƏLAVƏ

        return view('drivers.edit', compact('driver'));
    }

    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $this->authorize('update', $driver);  // ✅ ƏLAVƏ

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

        $this->authorize('delete', $driver);  // ✅ ƏLAVƏ

        $driver->delete();

        return redirect()->route('drivers.index')->with('success', 'Sürücü uğurla silindi!');
    }

    public function importForm()
    {
        $this->authorize('import', Driver::class);  // ✅ ƏLAVƏ

        return view('drivers.import');
    }

    public function import(Request $request)
    {
        $this->authorize('import', Driver::class);  // ✅ ƏLAVƏ

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(
                new DriversImport(
                    (int) session('current_garage_id'),
                    session('current_company_id') ? (int) session('current_company_id') : null
                ),
                $request->file('file')
            );

            return redirect()->route('drivers.index')->with('success', 'Sürücülər uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'Sürücü idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }

    public function export()
    {
        $this->authorize('export', Driver::class);  // ✅ ƏLAVƏ

        return Excel::download(new DriversExport, 'suruculer.xlsx');
    }
}
