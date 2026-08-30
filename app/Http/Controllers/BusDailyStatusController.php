<?php

namespace App\Http\Controllers;

use App\Models\BusDailyStatus;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BusDailyStatusesImport;
use Illuminate\Validation\Rule;

class BusDailyStatusController extends Controller
{
    public function index()
    {
        // 🔥 DƏYİŞİKLİK: paginate(50) əlavə edildi
        $statuses = BusDailyStatus::with('bus')
            ->orderBy('tarix', 'desc')
            ->paginate(config('settings.pagination'));
        return view('bus-daily-statuses.index', compact('statuses'));
    }

    public function create()
    {
        $buses = \App\Models\Bus::orderBy('dqn')->get();
        return view('bus-daily-statuses.create', compact('buses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bus_id' => ['required', Rule::exists('buses', 'id')->where('garage_id', session('current_garage_id'))],
            'tarix'  => 'required|date',
            'status' => 'required|string',
        ]);

        // ✅ DÜZƏLİŞ: Eyni avtobus və tarix üçün qeyd varsa, xəta ver
        $exists = BusDailyStatus::where('bus_id', $request->bus_id)
            ->whereDate('tarix', $request->tarix)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'tarix' => "Bu avtobus üçün {$request->tarix} tarixində artıq status qeydi var!"
            ])->withInput();
        }

        BusDailyStatus::create($validated);
        return redirect()->route('bus-daily-statuses.index')->with('success', 'Status uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $status = BusDailyStatus::with('bus')->findOrFail($id);
        return view('bus-daily-statuses.show', compact('status'));
    }

    public function edit($id)
    {
        $status = BusDailyStatus::findOrFail($id);
        $buses = \App\Models\Bus::orderBy('dqn')->get();
        return view('bus-daily-statuses.edit', compact('status', 'buses'));
    }

    public function update(Request $request, $id)
    {
        $status = BusDailyStatus::findOrFail($id);

        $validated = $request->validate([
            'bus_id' => ['required', Rule::exists('buses', 'id')->where('garage_id', session('current_garage_id'))],
            'tarix'  => 'required|date',
            'status' => 'required|string',
        ]);

        // ✅ DÜZƏLİŞ: Eyni avtobus və tarix üçün başqa qeyd varsa (özündən başqa), xəta ver
        $exists = BusDailyStatus::where('bus_id', $request->bus_id)
            ->where('id', '!=', $id)
            ->whereDate('tarix', $request->tarix)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'tarix' => "Bu avtobus üçün {$request->tarix} tarixində artıq status qeydi var!"
            ])->withInput();
        }

        $status->update($validated);
        return redirect()->route('bus-daily-statuses.index')->with('success', 'Status yeniləndi!');
    }

    public function destroy($id)
    {
        $status = BusDailyStatus::findOrFail($id);
        $status->delete();
        return redirect()->route('bus-daily-statuses.index')->with('success', 'Status silindi!');
    }

    public function importForm()
    {
        return view('bus-daily-statuses.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(new BusDailyStatusesImport, $request->file('file'));
            return redirect()->route('bus-daily-statuses.index')->with('success', 'Statuslar uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('bus-daily-statuses.index')->with('error', 'Status idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }
}
