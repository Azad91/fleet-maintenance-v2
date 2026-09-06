<?php

namespace App\Http\Controllers;

use App\Models\DailyKmRecord;
use App\Models\Bus;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DailyKmRecordsImport;
use Illuminate\Validation\Rule;

class DailyKmRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DailyKmRecord::class);  // ✅ ƏLAVƏ

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

        $records = $query->orderBy('date', 'desc')->paginate(config('settings.pagination', 15));
        return view('daily-km-records.index', compact('records', 'search'));
    }

    public function create()
    {
        $this->authorize('create', DailyKmRecord::class);  // ✅ ƏLAVƏ

        $buses = Bus::orderBy('dqn')->get();
        return view('daily-km-records.create', compact('buses'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', DailyKmRecord::class);  // ✅ ƏLAVƏ

        $validated = $request->validate([
            'bus_id' => ['required', Rule::exists('buses', 'id')->where('garage_id', session('current_garage_id'))],
            'date' => 'required|date',
            'km' => 'required|integer|min:0',
        ]);

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
                'km' => "KM dəyəri əvvəlki qeyddən ({$previousKm->km}) böyük olmalıdır!"
            ])->withInput();
        }

        if ($nextKm && $request->km >= $nextKm->km) {
            return back()->withErrors([
                'km' => "KM dəyəri sonrakı qeyddən ({$nextKm->km}) kiçik olmalıdır!"
            ])->withInput();
        }

        $exists = DailyKmRecord::where('bus_id', $request->bus_id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => "Bu avtobus üçün {$request->date} tarixində artıq KM qeydi var!"
            ])->withInput();
        }

        DailyKmRecord::create($validated);
        return redirect()->route('daily-km-records.index')->with('success', 'KM məlumatı uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $record = DailyKmRecord::with('bus')->findOrFail($id);

        $this->authorize('view', $record);  // ✅ ƏLAVƏ

        $history = DailyKmRecord::where('bus_id', $record->bus_id)
                    ->orderBy('date', 'desc')
                    ->get();
        return view('daily-km-records.show', compact('record', 'history'));
    }

    public function edit($id)
    {
        $record = DailyKmRecord::findOrFail($id);

        $this->authorize('update', $record);  // ✅ ƏLAVƏ

        $buses = Bus::orderBy('dqn')->get();
        return view('daily-km-records.edit', compact('record', 'buses'));
    }

    public function update(Request $request, $id)
    {
        $record = DailyKmRecord::findOrFail($id);

        $this->authorize('update', $record);  // ✅ ƏLAVƏ

        $validated = $request->validate([
            'bus_id' => ['required', Rule::exists('buses', 'id')->where('garage_id', session('current_garage_id'))],
            'date' => 'required|date',
            'km' => 'required|integer|min:0',
        ]);

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
                'km' => "KM dəyəri əvvəlki qeyddən ({$previousKm->km}) böyük olmalıdır!"
            ])->withInput();
        }

        if ($nextKm && $request->km >= $nextKm->km) {
            return back()->withErrors([
                'km' => "KM dəyəri sonrakı qeyddən ({$nextKm->km}) kiçik olmalıdır!"
            ])->withInput();
        }

        $exists = DailyKmRecord::where('bus_id', $request->bus_id)
            ->where('id', '!=', $id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => "Bu avtobus üçün {$request->date} tarixində artıq KM qeydi var!"
            ])->withInput();
        }

        $record->update($validated);
        return redirect()->route('daily-km-records.index')->with('success', 'KM məlumatı yeniləndi!');
    }

    public function destroy($id)
    {
        $record = DailyKmRecord::findOrFail($id);

        $this->authorize('delete', $record);  // ✅ ƏLAVƏ

        $record->delete();
        return redirect()->route('daily-km-records.index')->with('success', 'KM məlumatı silindi!');
    }

    public function importForm()
    {
        $this->authorize('import', DailyKmRecord::class);  // ✅ ƏLAVƏ

        return view('daily-km-records.import');
    }

    public function import(Request $request)
    {
        $this->authorize('import', DailyKmRecord::class);  // ✅ ƏLAVƏ

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(
                new DailyKmRecordsImport(
                    (int) session('current_garage_id'),
                    session('current_company_id') ? (int) session('current_company_id') : null
                ),
                $request->file('file')
            );
            return redirect()->route('daily-km-records.index')->with('success', 'KM məlumatları uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('daily-km-records.index')->with('error', 'KM idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }
}
