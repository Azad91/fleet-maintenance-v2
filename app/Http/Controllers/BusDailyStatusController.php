<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusDailyStatusStoreRequest;
use App\Http\Requests\BusDailyStatusUpdateRequest;
use App\Imports\BusDailyStatusesImport;
use App\Models\Bus;
use App\Exports\BusDailyStatusesExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Models\BusDailyStatus;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BusDailyStatusController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', BusDailyStatus::class);

        // `date` defaults to today for display purposes.
        // However, `dateWasExplicit` records whether the user actually
        // supplied a date filter — this drives the "delete all" button,
        // which wipes every date when no explicit filter is applied.
        $dateWasExplicit = $request->filled('date');
        $date = $dateWasExplicit ? $request->input('date') : now()->toDateString();

        $dqn = $request->input('dqn');
        $status = $request->input('status');

        $query = BusDailyStatus::with('bus');

        if ($dateWasExplicit && $date) {
            $query->whereDate('date', $date);
        } elseif (!$dateWasExplicit && $date) {
            // Default view: show today's records.
            $query->whereDate('date', $date);
        }

        if ($dqn) {
            $query->whereHas('bus', fn ($q) => $q->where('dqn', 'ILIKE', "%{$dqn}%"));
        }

        if ($status) {
            $query->where('status', $status);
        }

        $statuses = $query
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15))
            ->withQueryString();

        $availableStatuses = BusDailyStatus::query()
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->filter()
            ->values();

        // Total record count across ALL dates (used when no explicit
        // date filter is applied — the "delete all" button then wipes
        // the entire garage's status history, not just today's).
        $totalAll = BusDailyStatus::count();

        return view('bus-daily-statuses.index', compact(
            'statuses',
            'date',
            'dqn',
            'status',
            'availableStatuses',
            'dateWasExplicit',
            'totalAll',
        ));
    }

    public function create()
    {
        $this->authorize('create', BusDailyStatus::class);

        $buses = Bus::orderBy('dqn')->get();

        return view('bus-daily-statuses.create', compact('buses'));
    }

    public function store(BusDailyStatusStoreRequest $request)
    {
        $this->authorize('create', BusDailyStatus::class);

        $validated = $request->validated();

        $exists = BusDailyStatus::where('bus_id', $request->bus_id)
            ->whereDate('date', $request->date)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => __('messages.flash.duplicate_date', ['date' => $request->date]),
            ])->withInput();
        }

        BusDailyStatus::create($validated);

        return redirect()->route('bus-daily-statuses.index')
            ->with('success', __('messages.flash.created', ['Item' => 'Status']));
    }

    public function show($id)
    {
        $status = BusDailyStatus::with('bus')->findOrFail($id);

        $this->authorize('view', $status);

        return view('bus-daily-statuses.show', compact('status'));
    }

    public function edit($id)
    {
        $status = BusDailyStatus::findOrFail($id);

        $this->authorize('update', $status);

        $buses = Bus::orderBy('dqn')->get();

        return view('bus-daily-statuses.edit', compact('status', 'buses'));
    }

    public function update(BusDailyStatusUpdateRequest $request, $id)
    {
        $status = BusDailyStatus::findOrFail($id);
        $this->authorize('update', $status);

        $validated = $request->validated();

        $exists = BusDailyStatus::where('bus_id', $request->bus_id)
            ->where('id', '!=', $id)
            ->whereDate('date', $request->date)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => __('messages.flash.duplicate_date', ['date' => $request->date]),
            ])->withInput();
        }

        $status->update($validated);

        return redirect()->route('bus-daily-statuses.index')
            ->with('success', __('messages.flash.updated', ['Item' => 'Status']));
    }

    public function destroy($id)
    {
        $status = BusDailyStatus::findOrFail($id);

        $this->authorize('delete', $status);

        $status->delete();

        return redirect()->route('bus-daily-statuses.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'Status']));
    }

    public function importForm()
    {
        $this->authorize('import', BusDailyStatus::class);

        return view('bus-daily-statuses.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', BusDailyStatus::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new BusDailyStatusesImport(
                $garageId,
                GarageContext::resolveCompanyId(),
            );

            Excel::import($import, $request->file('file'));

            $skipped = $import->skipped;
            $imported = $import->importedCount;

            if (empty($skipped)) {
                return redirect()->route('bus-daily-statuses.index')
                    ->with('success', __('messages.flash.import_success', [
                        'count' => $imported,
                        'items' => 'statuses',
                    ]));
            }

            return redirect()->route('bus-daily-statuses.index')
                ->with('warning', __('messages.flash.import_partial'))
                ->with('import_report', $this->buildImportReport($imported, $skipped, collect()));

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('bus-daily-statuses.index')
                ->with('error', __('messages.flash.import_error'));
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', BusDailyStatus::class);

        $date = $request->filled('date') ? $request->input('date') : now()->toDateString();
        $dqn = $request->input('dqn');
        $status = $request->input('status');

        $filename = 'bus-daily-statuses-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(
            new BusDailyStatusesExport($date, $dqn, $status),
            $filename
        );
    }

    /**
     * Bulk soft-delete bus daily statuses matching the current filter.
     *
     * If no explicit date filter is present in the request, the date
     * filter is IGNORED — the operation then wipes every status record
     * for the current garage, not just today's.
     */
    public function bulkDeleteAll(Request $request): RedirectResponse
    {
        $this->authorize('delete', BusDailyStatus::class);

        @set_time_limit(300);

        $date = $request->filled('date') ? $request->input('date') : null;
        $dqn = $request->input('dqn');
        $status = $request->input('status');

        $query = BusDailyStatus::query();

        if ($date) {
            $query->whereDate('date', $date);
        }

        if ($dqn) {
            $query->whereHas('bus', fn ($q) => $q->where('dqn', 'ILIKE', "%{$dqn}%"));
        }

        if ($status) {
            $query->where('status', $status);
        }

        $count = $query->delete();

        if ($count === 0) {
            return redirect()
                ->route('bus-daily-statuses.index')
                ->with('error', __('messages.flash.none_selected'));
        }

        return redirect()
            ->route('bus-daily-statuses.index')
            ->with('success', __('messages.flash.bulk_deleted', [
                'count' => $count,
                'items' => 'statuses',
            ]));
    }
}
