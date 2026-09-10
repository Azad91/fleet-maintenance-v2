<?php

namespace App\Http\Controllers;

use App\Exports\DriversExport;
use App\Http\Requests\DriverStoreRequest;
use App\Http\Requests\DriverUpdateRequest;
use App\Imports\DriversImport;
use App\Models\Driver;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DriverController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Driver::class);

        $drivers = Driver::orderBy('code')->paginate(config('settings.pagination', 30));

        return view('drivers.index', compact('drivers'));
    }

    public function create()
    {
        $this->authorize('create', Driver::class);

        return view('drivers.create');
    }

    public function store(DriverStoreRequest $request)
    {
        $this->authorize('create', Driver::class);

        Driver::create($request->validated());

        return redirect()->route('drivers.index')->with('success', 'Sürücü uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $driver = Driver::findOrFail($id);
        $this->authorize('view', $driver);

        return view('drivers.show', compact('driver'));
    }

    public function edit($id)
    {
        $driver = Driver::findOrFail($id);
        $this->authorize('update', $driver);

        return view('drivers.edit', compact('driver'));
    }

    public function update(DriverUpdateRequest $request, $id)
    {
        $driver = Driver::findOrFail($id);
        $this->authorize('update', $driver);

        $driver->update($request->validated());

        return redirect()->route('drivers.index')->with('success', 'Sürücü uğurla yeniləndi!');
    }

    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $this->authorize('delete', $driver);

        $driver->delete();

        return redirect()->route('drivers.index')->with('success', 'Sürücü uğurla silindi!');
    }

    public function importForm()
    {
        $this->authorize('import', Driver::class);

        return view('drivers.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('import', Driver::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new DriversImport(
                (int) GarageContext::getGarageId(),
                GarageContext::getCompanyId() ? (int) GarageContext::getCompanyId() : null
            );

            Excel::import($import, $request->file('file'));

            $skipped  = $import->skipped;
            $imported = $import->importedCount;

            if (empty($skipped)) {
                return redirect()->route('drivers.index')
                    ->with('success', "✅ {$imported} sürücü uğurla idxal edildi.");
            }

            return redirect()->route('drivers.index')
                ->with('warning', '⚠️ İdxal tamamlandı, lakin bəzi sətirlər atlandı.')
                ->with('import_report', $this->buildImportReport($imported, $skipped, collect()));

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('drivers.index')
                ->with('error', 'İdxal zamanı xəta baş verdi.');
        }
    }

    public function export()
    {
        $this->authorize('export', Driver::class);

        return Excel::download(new DriversExport, 'suruculer.xlsx');
    }
}
