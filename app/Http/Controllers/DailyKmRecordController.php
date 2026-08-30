<?php

namespace App\Http\Controllers;

use App\Models\DailyKmRecord;
use App\Models\Bus;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DailyKmRecordsImport;

class DailyKmRecordController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $query = DailyKmRecord::with('bus');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('bus', function ($bq) use ($search) {
                    $bq->where('dqn', 'ILIKE', "%{$search}%")
                       ->orWhere('xett_no', 'ILIKE', "%{$search}%");
                })->orWhere('tarix', 'ILIKE', "%{$search}%");
            });
        }

        $records = $query->orderBy('tarix', 'desc')->paginate(100);
        return view('daily-km-records.index', compact('records', 'search'));
    }

    public function create()
    {
        $buses = Bus::orderBy('dqn')->get();
        return view('daily-km-records.create', compact('buses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'tarix' => 'required|date',
            'km' => 'required|integer|min:0',
        ]);

        // HasGarageScope cari qaraj və şirkəti avtomatik yazır.
        $bus = Bus::findOrFail($request->bus_id);
        $previousKm = $bus->dailyKmRecords()
            ->whereDate('tarix', '<', $request->tarix)
            ->orderByDesc('tarix')
            ->first();
        $nextKm = $bus->dailyKmRecords()
            ->whereDate('tarix', '>', $request->tarix)
            ->orderBy('tarix')
            ->first();

        if ($previousKm && $request->km <= $previousKm->km) {
            return back()->withErrors([
                'km' => "KM dəyəri əvvəlki qeyddən ({$previousKm->km}) böyük olmalıdır!"
            ])->withInput();
        }

        if ($nextKm && $request->km >= $nextKm->km) {
            return back()->withErrors([
                'km' => "KM dəyəri sonrakı qeyddən ({$nextKm->km}) kiçik olmalıdır!"
            ])->withInput();
        }

        // 🔥 BİZNES QAYDASI: Eyni günə 2-ci qeyd əngəllənsin
        $exists = DailyKmRecord::where('bus_id', $request->bus_id)
            ->whereDate('tarix', $request->tarix)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'tarix' => "Bu avtobus üçün {$request->tarix} tarixində artıq KM qeydi var!"
            ])->withInput();
        }

        DailyKmRecord::create($validated);
        return redirect()->route('daily-km-records.index')->with('success', 'KM məlumatı uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $record = DailyKmRecord::with('bus')->findOrFail($id);
        $history = DailyKmRecord::where('bus_id', $record->bus_id)
                    ->orderBy('tarix', 'desc')
                    ->get();
        return view('daily-km-records.show', compact('record', 'history'));
    }

    public function edit($id)
    {
        $record = DailyKmRecord::findOrFail($id);
        $buses = Bus::orderBy('dqn')->get();
        return view('daily-km-records.edit', compact('record', 'buses'));
    }

    public function update(Request $request, $id)
    {
        $record = DailyKmRecord::findOrFail($id);

        $validated = $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'tarix' => 'required|date',
            'km' => 'required|integer|min:0',
        ]);

        $bus = Bus::findOrFail($request->bus_id);
        $previousKm = $bus->dailyKmRecords()
            ->where('id', '!=', $id)
            ->whereDate('tarix', '<', $request->tarix)
            ->orderByDesc('tarix')
            ->first();
        $nextKm = $bus->dailyKmRecords()
            ->where('id', '!=', $id)
            ->whereDate('tarix', '>', $request->tarix)
            ->orderBy('tarix')
            ->first();

        if ($previousKm && $request->km <= $previousKm->km) {
            return back()->withErrors([
                'km' => "KM dəyəri əvvəlki qeyddən ({$previousKm->km}) böyük olmalıdır!"
            ])->withInput();
        }

        if ($nextKm && $request->km >= $nextKm->km) {
            return back()->withErrors([
                'km' => "KM dəyəri sonrakı qeyddən ({$nextKm->km}) kiçik olmalıdır!"
            ])->withInput();
        }

        // 🔥 BİZNES QAYDASI: Eyni günə başqa qeyd varsa (özündən başqa)
        $exists = DailyKmRecord::where('bus_id', $request->bus_id)
            ->where('id', '!=', $id)
            ->whereDate('tarix', $request->tarix)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'tarix' => "Bu avtobus üçün {$request->tarix} tarixində artıq KM qeydi var!"
            ])->withInput();
        }

        $record->update($validated);
        return redirect()->route('daily-km-records.index')->with('success', 'KM məlumatı yeniləndi!');
    }

    public function destroy($id)
    {
        $record = DailyKmRecord::findOrFail($id);
        $record->delete();
        return redirect()->route('daily-km-records.index')->with('success', 'KM məlumatı silindi!');
    }

    public function importForm()
    {
        set_time_limit(600);
        return view('daily-km-records.import');
    }

    public function import(Request $request)
    {
        set_time_limit(1800);
        ini_set('memory_limit', '1024M');

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        try {
            Excel::import(new DailyKmRecordsImport, $request->file('file'));
            return redirect()->route('daily-km-records.index')->with('success', 'KM məlumatları uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('daily-km-records.index')->with('error', 'KM idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }
}
