<?php

namespace App\Http\Controllers;

use App\Imports\MotorOilImport;
use App\Models\MotorOilDetail;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MotorOilController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', MotorOilDetail::class);

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

        if (! $request->ajax() && ! $request->wantsJson()) {
            return view('motor-oil.index', compact('grouped', 'search'));
        }

        return view('motor-oil.partials.table', compact('grouped', 'search'));
    }

    public function importForm()
    {
        $this->authorize('import', MotorOilDetail::class);

        return view('motor-oil.import');
    }

    public function import(Request $request): RedirectResponse
    {
        // Resolve garage context BEFORE authorization, matching every
        // other import controller. Without it the MotorOilDetail model
        // would throw MissingGarageContextException during the import.
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', MotorOilDetail::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new MotorOilImport(
                $garageId,
                GarageContext::resolveCompanyId(),
            );

            Excel::import($import, $request->file('file'));

            $skipped = $import->skipped;
            $imported = $import->importedCount;

            if (empty($skipped)) {
                return redirect()->route('motor-oil.index')
                    ->with('success', __('messages.flash.import_success', [
                        'count' => $imported,
                        'items' => 'motor oil details',
                    ]));
            }

            return redirect()->route('motor-oil.index')
                ->with('warning', __('messages.flash.import_partial'))
                ->with('import_report', $this->buildImportReport($imported, $skipped, collect()));

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('motor-oil.index')
                ->with('error', __('messages.flash.import_error'));
        }
    }

    /**
     * Bulk delete ALL motor oil details matching the current search filter.
     *
     * The Motor Oil catalog is a flat catalog (no soft-delete on the
     * model), so the rows are removed permanently. The import can
     * re-add them at any time.
     */
    public function bulkDeleteAll(Request $request): RedirectResponse
    {
        $this->authorize('delete', MotorOilDetail::class);

        @set_time_limit(300);

        $search = $request->input('search');

        // Normalize the search input the same way search() does — the
        // filter is a KM value that may contain dots/spaces.
        $normalizedKm = $search !== null
            ? preg_replace('/[^\d]/', '', (string) $search)
            : null;

        $query = MotorOilDetail::query();

        if ($normalizedKm !== null && $normalizedKm !== '') {
            $query->where('km', (int) $normalizedKm);
        }

        $count = $query->delete();

        if ($count === 0) {
            return redirect()
                ->route('motor-oil.index')
                ->with('error', __('messages.flash.none_selected'));
        }

        return redirect()
            ->route('motor-oil.index')
            ->with('success', __('messages.flash.bulk_deleted', [
                'count' => $count,
                'items' => 'motor oil details',
            ]));
    }
}
