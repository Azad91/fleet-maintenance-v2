<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusDailyStatusStoreRequest;
use App\Http\Requests\BusDailyStatusUpdateRequest;
use App\Imports\BusDailyStatusesImport;
use App\Models\Bus;
use App\Models\BusDailyStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BusDailyStatusController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', BusDailyStatus::class);

        $statuses = BusDailyStatus::with('bus')
            ->orderBy('date', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('bus-daily-statuses.index', compact('statuses'));
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
        $this->authorize('import', BusDailyStatus::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new BusDailyStatusesImport(
                (int) session('current_garage_id'),
                session('current_company_id') ? (int) session('current_company_id') : null
            );

            Excel::import($import, $request->file('file'));

            $skipped  = $import->skipped;
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
}
