<?php

namespace App\Http\Controllers;

use App\Enums\OilType;
use App\Imports\BusOilChangesImport;
use App\Models\BusOilChange;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        // Garage context must be resolved BEFORE authorization.
        // If no garage is active, redirect to the selection page
        // instead of triggering a 403 in the policy.
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', BusOilChange::class);

        $validated = $request->validate([
            'type' => ['required', \Illuminate\Validation\Rule::in(OilType::values())],
            'bus_length' => ['nullable', 'integer', \Illuminate\Validation\Rule::in([12, 18])],
            'file' => ['required', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $type = OilType::from($validated['type']);

        // The bus length only applies to motor oil imports — the
        // gearbox and axle schedules are brand-driven and do not
        // depend on the bus's physical length.
        $busLengthM = $type === OilType::Motor
            ? (int) ($validated['bus_length'] ?? 12)
            : null;

        // Debug-level: useful when diagnosing a failed import in
        // development. Hidden in production because LOG_LEVEL=warning
        // (see .env.production.example).
        Log::debug('Oil import started', [
            'type' => $type->value,
            'bus_length' => $busLengthM,
            'file' => $request->file('file')->getClientOriginalName(),
            'garage_id' => $garageId,
        ]);

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

            Log::debug('Oil import finished', [
                'type' => $type->value,
                'imported' => $imported,
                'skipped' => count($skipped),
            ]);

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
            // ERROR level: the import failed and the operator needs
            // to know. The full stack trace is NOT logged here —
            // Laravel's exception handler already forwards it to
            // Sentry (config/sentry.php), and duplicating a multi-KB
            // trace into the local log file adds noise without value.
            Log::error('Oil import failed', [
                'type' => $type->value,
                'file' => $request->file('file')?->getClientOriginalName(),
                'garage_id' => $garageId,
                'error' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ]);

            report($e);

            return redirect()
                ->route('oil-changes.import')
                ->with('error', __('messages.flash.import_error'));
        }
    }
}
