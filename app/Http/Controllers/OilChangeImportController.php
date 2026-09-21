<?php

namespace App\Http\Controllers;

use App\Enums\OilType;
use App\Imports\BusOilChangesImport;
use App\Models\BusOilChange;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class OilChangeImportController extends Controller
{
    public function form(): View
    {
        $this->authorize('import', BusOilChange::class);

        return view('oil-changes.import');
    }

    public function store(Request $request): RedirectResponse
    {
        // Resolve garage context BEFORE authorization.
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', BusOilChange::class);

        $validated = $request->validate([
            'type'        => ['required', \Illuminate\Validation\Rule::in(OilType::values())],
            'bus_length'  => ['nullable', 'integer', \Illuminate\Validation\Rule::in([12, 18])],
            'file'        => ['required', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $type = OilType::from($validated['type']);
        $busLengthM = $type === OilType::Motor
            ? (int) ($validated['bus_length'] ?? 12)
            : null;

        try {
            $import = new BusOilChangesImport(
                $garageId,
                GarageContext::resolveCompanyId(),
                $type,
                $busLengthM,
            );

            Excel::import($import, $request->file('file'));

            $skipped = $import->skipped;
            $imported = $import->importedCount;

            if (empty($skipped)) {
                return redirect()
                    ->route('oil-changes.index', ['type' => $type->value])
                    ->with('success', __('messages.flash.import_success', [
                        'count' => $imported,
                        'items' => $type->label(),
                    ]));
            }

            return redirect()
                ->route('oil-changes.index', ['type' => $type->value])
                ->with('warning', __('messages.flash.import_partial'))
                ->with('import_report', $this->buildImportReport($imported, $skipped, collect()));

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('oil-changes.import')
                ->with('error', __('messages.flash.import_error'));
        }
    }
}
