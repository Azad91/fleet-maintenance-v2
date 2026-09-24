<?php

namespace App\Http\Controllers;

use App\Exports\DailyKmRecordsExport;
use App\Http\Requests\DailyKmStoreRequest;
use App\Http\Requests\DailyKmUpdateRequest;
use App\Imports\DailyKmRecordsImport;
use App\Models\Bus;
use App\Models\DailyKmRecord;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyKmRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DailyKmRecord::class);

        // Same pattern as bus-daily-statuses: display defaults to
        // today, but `dateWasExplicit` records whether the user
        // actually supplied a date filter.
        $dateWasExplicit = $request->filled('date');
        $date = $dateWasExplicit ? $request->input('date') : now()->toDateString();

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

        // Total record count across ALL dates — used by the "delete all"
        // button when no explicit date filter is applied.
        $totalAll = DailyKmRecord::count();

        return view('daily-km-records.index', compact(
            'records',
            'date',
            'dqn',
            'dateWasExplicit',
            'totalAll',
        ));
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

        try {
            DailyKmRecord::create($validated);
        } catch (\Throwable $e) {
            // Race-condition guard: another request may have inserted
            // the same (bus, date) between our exists() check and the
            // create(). The partial unique index rejects the second
            // insert with SQLSTATE 23505; surface it as a normal
            // validation error instead of a raw 500.
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            return back()->withErrors([
                'date' => __('messages.flash.km_already_recorded', ['date' => $request->date]),
            ])->withInput();
        }

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

        try {
            $record->update($validated);
        } catch (\Throwable $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            return back()->withErrors([
                'date' => __('messages.flash.km_already_recorded', ['date' => $request->date]),
            ])->withInput();
        }

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

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', DailyKmRecord::class);

        $date = $request->filled('date') ? $request->input('date') : now()->toDateString();
        $dqn = $request->input('dqn');

        $filename = 'daily-km-records-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(
            new DailyKmRecordsExport($date, $dqn),
            $filename
        );
    }

    /**
     * Bulk soft-delete daily KM records matching the current filter.
     *
     * If no explicit date filter is present in the request, the date
     * filter is IGNORED — the operation then wipes every KM record
     * for the current garage, not just today's.
     *
     * AUDIT NOTE
     * ----------
     * A raw `$query->delete()` bypasses Eloquent per-model events,
     * so the Auditable trait's `deleted` handler never runs and no
     * audit log is written. We mirror the pattern used by
     * BusService::bulkDeleteAllByFilters():
     *
     *   1. Stream IDs from the database via cursor() in chunks of 500.
     *   2. Write the audit snapshot BEFORE each chunk's delete.
     *   3. Delete the chunk in one query.
     *
     * Chunking keeps memory usage flat for very large datasets (a
     * garage can accumulate hundreds of thousands of KM rows) and
     * keeps the audit snapshot in sync with what was actually deleted.
     */
    public function bulkDeleteAll(Request $request): RedirectResponse
    {
        $this->authorize('delete', DailyKmRecord::class);

        @set_time_limit(300);

        $date = $request->filled('date') ? $request->input('date') : null;
        $dqn = $request->input('dqn');

        $query = DailyKmRecord::query();

        if ($date) {
            $query->whereDate('date', $date);
        }

        if ($dqn) {
            $query->whereHas('bus', fn ($q) => $q->where('dqn', 'ILIKE', "%{$dqn}%"));
        }

        $count = 0;
        $buffer = [];

        foreach ($query->select('id')->cursor() as $row) {
            $buffer[] = $row->id;

            if (count($buffer) >= 500) {
                DailyKmRecord::auditBulkDelete($buffer);
                DailyKmRecord::whereIn('id', $buffer)->delete();
                $count += count($buffer);
                $buffer = [];
            }
        }

        if (! empty($buffer)) {
            DailyKmRecord::auditBulkDelete($buffer);
            DailyKmRecord::whereIn('id', $buffer)->delete();
            $count += count($buffer);
        }

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
