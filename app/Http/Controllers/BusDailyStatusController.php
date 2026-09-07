<?php

namespace App\Http\Controllers;

use App\Imports\BusDailyStatusesImport;
use App\Models\Bus;
use App\Models\BusDailyStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class BusDailyStatusController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', BusDailyStatus::class);  // ✅ ƏLAVƏ

        $statuses = BusDailyStatus::with('bus')
            ->orderBy('date', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('bus-daily-statuses.index', compact('statuses'));
    }

    public function create()
    {
        $this->authorize('create', BusDailyStatus::class);  // ✅ ƏLAVƏ

        $buses = Bus::orderBy('dqn')->get();

        return view('bus-daily-statuses.create', compact('buses'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', BusDailyStatus::class);  // ✅ ƏLAVƏ

        $validated = $request->validate([
            'bus_id' => ['required', Rule::exists('buses', 'id')->where('garage_id', session('current_garage_id'))],
            'date' => 'required|date',
            'status' => 'required|string',
        ]);

        $exists = BusDailyStatus::where('bus_id', $request->bus_id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => "Bu avtobus üçün {$request->date} tarixində artıq status qeydi var!",
            ])->withInput();
        }

        BusDailyStatus::create($validated);

        return redirect()->route('bus-daily-statuses.index')->with('success', 'Status uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $status = BusDailyStatus::with('bus')->findOrFail($id);

        $this->authorize('view', $status);  // ✅ ƏLAVƏ

        return view('bus-daily-statuses.show', compact('status'));
    }

    public function edit($id)
    {
        $status = BusDailyStatus::findOrFail($id);

        $this->authorize('update', $status);  // ✅ ƏLAVƏ

        $buses = Bus::orderBy('dqn')->get();

        return view('bus-daily-statuses.edit', compact('status', 'buses'));
    }

    public function update(Request $request, $id)
    {
        $status = BusDailyStatus::findOrFail($id);

        $this->authorize('update', $status);  // ✅ ƏLAVƏ

        $validated = $request->validate([
            'bus_id' => ['required', Rule::exists('buses', 'id')->where('garage_id', session('current_garage_id'))],
            'date' => 'required|date',
            'status' => 'required|string',
        ]);

        $exists = BusDailyStatus::where('bus_id', $request->bus_id)
            ->where('id', '!=', $id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => "Bu avtobus üçün {$request->date} tarixində artıq status qeydi var!",
            ])->withInput();
        }

        $status->update($validated);

        return redirect()->route('bus-daily-statuses.index')->with('success', 'Status yeniləndi!');
    }

    public function destroy($id)
    {
        $status = BusDailyStatus::findOrFail($id);

        $this->authorize('delete', $status);  // ✅ ƏLAVƏ

        $status->delete();

        return redirect()->route('bus-daily-statuses.index')->with('success', 'Status silindi!');
    }

    public function importForm()
    {
        $this->authorize('import', BusDailyStatus::class);  // ✅ ƏLAVƏ

        return view('bus-daily-statuses.import');
    }

    public function import(Request $request)
    {
        $this->authorize('import', BusDailyStatus::class);  // ✅ ƏLAVƏ

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
