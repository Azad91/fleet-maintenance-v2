<?php

namespace App\Http\Controllers;

use App\Imports\MotorOilImport;
use App\Models\MotorOilDetail;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;

class MotorOilController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', MotorOilDetail::class);  // ✅ ƏLAVƏ

        $details = MotorOilDetail::orderBy('km')->orderBy('part_name')->get();
        $grouped = $details->groupBy('km');

        return view('motor-oil.index', compact('grouped'));
    }

    public function search(Request $request)
    {
        $this->authorize('viewAny', MotorOilDetail::class);

        $search = preg_replace('/[^\d]/', '', (string) $request->search);

        $details = MotorOilDetail::when($search, function ($query, $search) {
            return $query->where('km', (int) $search);
        })
            ->orderBy('km')
            ->orderBy('part_name')
            ->get();

        $grouped = $details->groupBy('km');

        // ✅ ƏLAVƏ: Əgər AJAX deyilsə, tam səhifə qaytar
        if (! $request->ajax() && ! $request->wantsJson()) {
            return view('motor-oil.index', compact('grouped', 'search'));
        }

        return view('motor-oil.partials.table', compact('grouped', 'search'));
    }

    public function importForm()
    {
        $this->authorize('import', MotorOilDetail::class);  // ✅ ƏLAVƏ

        return view('motor-oil.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('import', MotorOilDetail::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new MotorOilImport();
            Excel::import($import, $request->file('file'));

            $skipped  = $import->skipped;
            $imported = $import->importedCount;

            if (empty($skipped)) {
                return redirect()->route('motor-oil.index')
                    ->with('success', "✅ {$imported} motor yağ detalı uğurla idxal edildi.");
            }

            return redirect()->route('motor-oil.index')
                ->with('warning', '⚠️ İdxal tamamlandı, lakin bəzi sətirlər atlandı.')
                ->with('import_report', $this->buildImportReport($imported, $skipped, collect()));

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('motor-oil.index')
                ->with('error', 'İdxal zamanı xəta baş verdi.');
        }
    }
}
