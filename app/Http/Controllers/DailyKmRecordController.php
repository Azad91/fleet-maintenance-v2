<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyKmStoreRequest;
use App\Http\Requests\DailyKmUpdateRequest;
use App\Imports\DailyKmRecordsImport;
use App\Models\Bus;
use App\Models\DailyKmRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DailyKmRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DailyKmRecord::class);

        $search = $request->search;
        $query = DailyKmRecord::with('bus');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('bus', function ($bq) use ($search) {
                    $bq->where('dqn', 'ILIKE', "%{$search}%")
                        ->orWhere('route_number', 'ILIKE', "%{$search}%");
                })->orWhere('date', 'ILIKE', "%{$search}%");
            });
        }

        $records = $query->orderBy('date', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('daily-km-records.index', compact('records', 'search'));
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
        $this->authorize('import', DailyKmRecord::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new DailyKmRecordsImport(
                (int) session('current_garage_id'),
                session('current_company_id') ? (int) session('current_company_id') : null
            );

            Excel::import($import, $request->file('file'));

            $skipped  = $import->skipped;
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
}
