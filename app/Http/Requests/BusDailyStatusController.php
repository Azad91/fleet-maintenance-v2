<?php

namespace App\Http\Controllers;

use App\Models\BusDailyStatus;
use App\Models\Bus;
use App\Http\Requests\BusDailyStatusStoreRequest;
use App\Http\Requests\BusDailyStatusUpdateRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BusDailyStatusesImport;
use Illuminate\Validation\Rule;

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

    // ✅ DƏYİŞİKLİK: BusDailyStatusStoreRequest istifadə olunur
    public function store(BusDailyStatusStoreRequest $request)
    {
        $this->authorize('create', BusDailyStatus::class);

        $validated = $request->validated();

        $exists = BusDailyStatus::where('bus_id', $request->bus_id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => "Bu avtobus üçün {$request->date} tarixində artıq status qeydi var!"
            ])->withInput();
        }

        BusDailyStatus::create($validated);
        return redirect()->route('bus-daily-statuses.index')->with('success', 'Status uğurla əlavə edildi!');
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

    // ✅ DƏYİŞİKLİK: BusDailyStatusUpdateRequest istifadə olunur
    public function update(BusDailyStatusUpdateRequest $request, $id)
    {
        $status = BusDailyStatus::findOrFail($id);

        $this->authorize('update', $status);

        $validated = $request->validated();

        $exists = BusDailyStatus::where('bus_id', $request->bus_id)
            ->where('id', '!=', $id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => "Bu avtobus üçün {$request->date} tarixində artıq status qeydi var!"
            ])->withInput();
        }

        $status->update($validated);
        return redirect()->route('bus-daily-statuses.index')->with('success', 'Status yeniləndi!');
    }

    public function destroy($id)
    {
        $status = BusDailyStatus::findOrFail($id);

        $this->authorize('delete', $status);

        $status->delete();
        return redirect()->route('bus-daily-statuses.index')->with('success', 'Status silindi!');
    }

    public function importForm()
    {
        $this->authorize('import', BusDailyStatus::class);

        return view('bus-daily-statuses.import');
    }

    public function import(Request $request)
    {
        $this->authorize('import', BusDailyStatus::class);

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(
                new BusDailyStatusesImport(
                    (int) session('current_garage_id'),
                    session('current_company_id') ? (int) session('current_company_id') : null
                ),
                $request->file('file')
            );
            return redirect()->route('bus-daily-statuses.index')->with('success', 'Statuslar uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('bus-daily-statuses.index')->with('error', 'Status idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }
}
