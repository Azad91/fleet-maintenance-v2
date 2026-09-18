<?php

namespace App\Http\Controllers;

use App\Imports\MotorOilImport;
use App\Models\BusBrand;
use App\Models\MotorOilDetail;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MotorOilController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MotorOilDetail::class);

        $brandId = $request->input('brand_id');
        $brands = BusBrand::active()->orderBy('name')->get();

        $query = MotorOilDetail::with('brand');

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        $details = $query->orderBy('km')->orderBy('part_name')->get();
        $grouped = $details->groupBy('km');

        return view('motor-oil.index', compact('grouped', 'brands', 'brandId'));
    }

    public function search(Request $request)
    {
        $this->authorize('viewAny', MotorOilDetail::class);

        $brandId = $request->input('brand_id');
        $search = preg_replace('/[^\d]/', '', (string) $request->search);

        $query = MotorOilDetail::with('brand');

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        if ($search) {
            $query->where('km', (int) $search);
        }

        $details = $query
            ->orderBy('km')
            ->orderBy('part_name')
            ->get();

        $grouped = $details->groupBy('km');
        $brands = BusBrand::active()->orderBy('name')->get();

        if (! $request->ajax() && ! $request->wantsJson()) {
            return view('motor-oil.index', compact('grouped', 'search', 'brands', 'brandId'));
        }

        return view('motor-oil.partials.table', compact('grouped', 'search'));
    }

    public function importForm()
    {
        $this->authorize('import', MotorOilDetail::class);

        $brands = BusBrand::active()->orderBy('name')->get();

        return view('motor-oil.import', compact('brands'));
    }

    public function import(Request $request): RedirectResponse
    {
        // Resolve garage context BEFORE authorization.
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', MotorOilDetail::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'brand_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('bus_brands', 'id')->where('garage_id', $garageId),
            ],
        ], [
            'brand_id.required' => __('messages.motor_oil.import_brand_required'),
        ]);

        try {
            $import = new MotorOilImport(
                $garageId,
                GarageContext::resolveCompanyId(),
                (int) $request->input('brand_id'),
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
     * Bulk delete ALL motor oil details matching the current filter.
     *
     * The Motor Oil catalog has no soft-delete — rows are removed
     * permanently. The import can re-add them at any time.
     */
    public function bulkDeleteAll(Request $request): RedirectResponse
    {
        $this->authorize('delete', MotorOilDetail::class);

        @set_time_limit(300);

        $brandId = $request->input('brand_id');
        $search = $request->input('search');

        $normalizedKm = $search !== null
            ? preg_replace('/[^\d]/', '', (string) $search)
            : null;

        $query = MotorOilDetail::query();

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

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
