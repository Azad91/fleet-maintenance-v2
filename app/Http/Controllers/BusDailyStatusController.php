<?php

namespace App\Http\Controllers;

use App\Models\BusDailyStatus;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BusDailyStatusesImport;

class BusDailyStatusController extends Controller
{
    public function index()
    {
        // 🔥 DƏYİŞİKLİK: paginate(50) əlavə edildi
        $statuses = BusDailyStatus::with('bus')
            ->orderBy('tarix', 'desc')
            ->paginate(50);
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
            'bus_id' => 'required|exists:buses,id',
            'tarix'  => 'required|date',
            'status' => 'required|string',
        ]);

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
            'bus_id' => 'required|exists:buses,id',
            'tarix'  => 'required|date',
            'status' => 'required|string',
        ]);
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
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        try {
            Excel::import(new BusDailyStatusesImport, $request->file('file'));
            return redirect()->route('bus-daily-statuses.index')->with('success', 'Statuslar uğurla idxal edildi!');
        } catch (\Exception $e) {
            return redirect()->route('bus-daily-statuses.index')->with('error', 'Xəta: ' . $e->getMessage());
        }
    }
}
