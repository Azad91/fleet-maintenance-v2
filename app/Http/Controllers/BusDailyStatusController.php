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

        // ── Filter parameters ──
        //
        // `date` defaults to today so the page opens on the most common
        // query (the operator checks the current day's statuses first
        // thing in the morning).
        //
        // An EMPTY date (`?date=`) means "all dates" — that's how the user
        // can browse the full history if they want. `has('date')` is what
        // distinguishes "not provided" (default to today) from "explicitly
        // empty" (show everything).
        $date = $request->has('date') ? $request->input('date') : now()->toDateString();
        $dqn = $request->input('dqn');
        $status = $request->input('status');

        $query = BusDailyStatus::with('bus');

        if ($date) {
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

        // Status dropdown values — sourced from the current garage's data
        // so it stays in sync with whatever Excel imports contain.
        // HasGarageScope filters this automatically.
        $availableStatuses = BusDailyStatus::query()
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->filter()
            ->values();

        return view('bus-daily-statuses.index', compact(
            'statuses',
            'date',
            'dqn',
            'status',
            'availableStatuses',
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
        // Resolve garage first so that a missing context redirects to
        // garage selection instead of triggering a 403 in the policy.
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
    /**
     * Export the currently-filtered status list to Excel.
     *
     * Uses exactly the same filters as index() so that "what you see
     * is what you export" — the operator can filter on screen, click
     * the button, and get the same rows in the spreadsheet.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', BusDailyStatus::class);

        $date = $request->has('date') ? $request->input('date') : now()->toDateString();
        $dqn = $request->input('dqn');
        $status = $request->input('status');

        $filename = 'bus-daily-statuses-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(
            new BusDailyStatusesExport($date, $dqn, $status),
            $filename
        );
    }
}
