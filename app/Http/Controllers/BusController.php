<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Http\Requests\BusStoreRequest;
use App\Http\Requests\BusUpdateRequest;
use Illuminate\Http\Request;

class BusController extends Controller
{
    // ====== INDEX ======
    public function index()
    {
        // 🔥 DƏYİŞİKLİK: paginate(50) əlavə edildi
        $buses = Bus::with('latestKmRecord')->orderBy('id', 'desc')->paginate(50);
        return view('buses.index', compact('buses'));
    }

    // ====== SEARCH ======
    public function search(Request $request)
    {
        $bus_project = $request->bus_project;
        $vin = $request->vin;
        $uzunluq = $request->uzunluq;
        $xett_no = $request->xett_no;
        $dqn = $request->dqn;
        $motor_no = $request->motor_no;

        $query = Bus::with('latestKmRecord');

        if (!empty($bus_project)) {
            $query->where('bus_project', 'ILIKE', "%{$bus_project}%");
        }
        if (!empty($vin)) {
            $query->where('vin', 'ILIKE', "%{$vin}%");
        }
        if (!empty($uzunluq)) {
            $query->where('uzunluq', 'ILIKE', "%{$uzunluq}%");
        }
        if (!empty($xett_no)) {
            $query->where('xett_no', 'ILIKE', "%{$xett_no}%");
        }
        if (!empty($dqn)) {
            $query->where('dqn', 'ILIKE', "%{$dqn}%");
        }
        if (!empty($motor_no)) {
            $query->where('motor_no', 'ILIKE', "%{$motor_no}%");
        }

        $buses = $query->orderBy('id', 'desc')->paginate(50);
        $isEmpty = $buses->isEmpty();

        if ($request->ajax()) {
            return view('buses.partials.table', compact('buses', 'isEmpty'))->render();
        }

        return view('buses.index', compact('buses'));
}

    // ====== SHOW ======
    public function show($id)
    {
        $bus = Bus::findOrFail($id);
        return view('buses.show', compact('bus'));
    }

    // ====== CREATE ======
    public function create()
    {
        return view('buses.create');
    }

    // ====== STORE ======
    public function store(BusStoreRequest $request)
    {
        $data = $request->validated();
        $data['tarix'] = now()->format('Y-m-d');

        // 🔥 QARAJ ID AVTOMATİK YAZ
        $data = $this->addGarageContext($data);

        Bus::create($data);

        return redirect()->route('buses.index')->with('success', 'Avtobus uğurla əlavə edildi!');
    }

    // ====== EDIT ======
    public function edit($id)
    {
        $bus = Bus::findOrFail($id);
        return view('buses.edit', compact('bus'));
    }

    // ====== UPDATE ======
    public function update(BusUpdateRequest $request, $id)
    {
        $bus = Bus::findOrFail($id);
        $data = $request->validated();

        // 🔥 QARAJ ID YENİLƏNMƏSİN (əgər istəmirsənsə)
        // $data['garage_id'] = session('current_garage_id');
        // $data['company_id'] = session('current_company_id');

        $bus->update($data);

        return redirect()->route('buses.index')->with('success', 'Avtobus uğurla yeniləndi!');
    }

    // ====== DESTROY ======
    public function destroy($id)
    {
        $bus = Bus::findOrFail($id);
        $bus->delete();
        return redirect()->route('buses.index')->with('success', 'Avtobus uğurla silindi!');
    }

    // ====== IMPORT ======
    public function importForm()
    {
        return view('buses.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\BusesImport, $request->file('file'));
            return redirect()->route('buses.index')->with('success', 'Avtobuslar uğurla idxal edildi!');
        } catch (\Exception $e) {
            return redirect()->route('buses.index')->with('error', 'Xəta baş verdi: ' . $e->getMessage());
        }
    }
}
