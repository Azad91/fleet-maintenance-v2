<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyKmStoreRequest;
use App\Http\Requests\DailyKmUpdateRequest;
use App\Imports\DailyKmRecordsImport;
use App\Models\Bus;
use App\Models\DailyKmRecord;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DailyKmRecordsExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyKmRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DailyKmRecord::class);

        // Same filtering model as bus-daily-statuses:
        // default to today, explicit empty date = all dates.
        $date = $request->has('date') ? $request->input('date') : now()->toDateString();
        $dqn = $request->input('dqn');

        $query = DailyKmRecord::with('bus');

        if ($date) {
            $query->whereDate('date', $date);
        }

        if ($dqn) {
            $query->whereHas('bus', fn ($q) => $q->where('dqn', 'ILIKE', "%{$dqn}%"));
        }

        $records = $query
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15))
            ->withQueryString();

        return view('daily-km-records.index', compact('records', 'date', 'dqn'));
    }

    public function create()
    {
        $this->authorize('create', DailyKmRecord::class);

        $buses = Bus::orderBy('dqn')->get();

        return view('daily-km-records.create', compact('buses'));
    }

    public function store(DailyKmStoreRequest $request)
    {
        $this->authorize('create', DailyKmRecord::class);

        $validated = $request->validated();

        $bus = Bus::findOrFail($request->bus_id);

        $previousKm = $bus->dailyKmRecords()
            ->whereDate('date', '<', $request->date)
            ->orderByDesc('date')
            ->first();

        $nextKm = $bus->dailyKmRecords()
            ->whereDate('date', '>', $request->date)
            ->orderBy('date')
            ->first();

        if ($previousKm && $request->km <= $previousKm->km) {
            return back()->withErrors([
                'km' => __('messages.flash.km_must_be_greater', ['km' => $previousKm->km]),
            ])->withInput();
        }

        if ($nextKm && $request->km >= $nextKm->km) {
            return back()->withErrors([
                'km' => __('messages.flash.km_must_be_less', ['km' => $nextKm->km]),
            ])->withInput();
        }

        $exists = DailyKmRecord::where('bus_id', $request->bus_id)
            ->whereDate('date', $request->date)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => __('messages.flash.km_already_recorded', ['date' => $request->date]),
            ])->withInput();
        }

        DailyKmRecord::create($validated);

        return redirect()->route('daily-km-records.index')
            ->with('success', __('messages.flash.created', ['Item' => 'KM record']));
    }

    public function show($id)
    {
        $record = DailyKmRecord::with('bus')->findOrFail($id);

        $this->authorize('view', $record);

        $history = DailyKmRecord::where('bus_id', $record->bus_id)
            ->orderBy('date', 'desc')
            ->get();

        return view('daily-km-records.show', compact('record', 'history'));
    }

    public function edit($id)
    {
        $record = DailyKmRecord::findOrFail($id);

        $this->authorize('update', $record);

        $buses = Bus::orderBy('dqn')->get();

        return view('daily-km-records.edit', compact('record', 'buses'));
    }

    public function update(DailyKmUpdateRequest $request, $id)
    {
        $record = DailyKmRecord::findOrFail($id);
        $this->authorize('update', $record);

        $validated = $request->validated();

        $bus = Bus::findOrFail($request->bus_id);

        $previousKm = $bus->dailyKmRecords()
            ->where('id', '!=', $id)
            ->whereDate('date', '<', $request->date)
            ->orderByDesc('date')
            ->first();

        $nextKm = $bus->dailyKmRecords()
            ->where('id', '!=', $id)
            ->whereDate('date', '>', $request->date)
            ->orderBy('date')
            ->first();

        if ($previousKm && $request->km <= $previousKm->km) {
            return back()->withErrors([
                'km' => __('messages.flash.km_must_be_greater', ['km' => $previousKm->km]),
            ])->withInput();
        }

        if ($nextKm && $request->km >= $nextKm->km) {
            return back()->withErrors([
                'km' => __('messages.flash.km_must_be_less', ['km' => $nextKm->km]),
            ])->withInput();
        }

        $exists = DailyKmRecord::where('bus_id', $request->bus_id)
            ->where('id', '!=', $id)
            ->whereDate('date', $request->date)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => __('messages.flash.km_already_recorded', ['date' => $request->date]),
            ])->withInput();
        }

        $record->update($validated);

        return redirect()->route('daily-km-records.index')
            ->with('success', __('messages.flash.updated', ['Item' => 'KM record']));
    }

    public function destroy($id)
    {
        $record = DailyKmRecord::findOrFail($id);

        $this->authorize('delete', $record);

        $record->delete();

        return redirect()->route('daily-km-records.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'KM record']));
    }

    public function importForm()
    {
        $this->authorize('import', DailyKmRecord::class);

        return view('daily-km-records.import');
    }

    public function import(Request $request): RedirectResponse
    {
        // Resolve garage first so that a missing context redirects to
        // garage selection instead of triggering a 403 in the policy.
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', DailyKmRecord::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new DailyKmRecordsImport(
                $garageId,
                GarageContext::resolveCompanyId(),
            );

            Excel::import($import, $request->file('file'));

            $skipped = $import->skipped;
            $imported = $import->importedCount;

            if (empty($skipped)) {
                return redirect()->route('daily-km-records.index')
                    ->with('success', __('messages.flash.import_success', [
                        'count' => $imported,
                        'items' => 'KM records',
                    ]));
            }

            return redirect()->route('daily-km-records.index')
                ->with('warning', __('messages.flash.import_partial'))
                ->with('import_report', $this->buildImportReport($imported, $skipped, collect()));

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('daily-km-records.index')
                ->with('error', __('messages.flash.import_error'));
        }
    }
    /**
     * Export the currently-filtered KM list to Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', DailyKmRecord::class);

        $date = $request->has('date') ? $request->input('date') : now()->toDateString();
        $dqn = $request->input('dqn');

        $filename = 'daily-km-records-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(
            new DailyKmRecordsExport($date, $dqn),
            $filename
        );
    }

    /**
     * Bulk soft-delete ALL daily KM records matching the current filter.
     *
     * Reuses the same filter logic as index() / export() so that
     * "what you see is what you delete".
     */
    public function bulkDeleteAll(Request $request): RedirectResponse
    {
        $this->authorize('delete', DailyKmRecord::class);

        @set_time_limit(300);

        $date = $request->has('date') ? $request->input('date') : now()->toDateString();
        $dqn = $request->input('dqn');

        $query = DailyKmRecord::query();

        if ($date) {
            $query->whereDate('date', $date);
        }

        if ($dqn) {
            $query->whereHas('bus', fn ($q) => $q->where('dqn', 'ILIKE', "%{$dqn}%"));
        }

        $count = $query->delete();

        if ($count === 0) {
            return redirect()
                ->route('daily-km-records.index')
                ->with('error', __('messages.flash.none_selected'));
        }

        return redirect()
            ->route('daily-km-records.index')
            ->with('success', __('messages.flash.bulk_deleted', [
                'count' => $count,
                'items' => 'KM records',
            ]));
    }
}
