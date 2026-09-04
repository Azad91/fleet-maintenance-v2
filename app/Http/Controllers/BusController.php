<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Http\Requests\BusStoreRequest;
use App\Http\Requests\BusUpdateRequest;
use Illuminate\Http\Request;

class BusController extends Controller
{
    public function index()
    {
        $buses = Bus::with('latestKmRecord')->orderBy('id', 'desc')->paginate(config('settings.pagination', 15));
        return view('buses.index', compact('buses'));
    }

    public function search(Request $request)
    {
        $bus_project = $request->bus_project;
        $vin = $request->vin;
        $uzunluq = $request->uzunluq;
        $route_number = $request->route_number; // əvvəl: xett_no
        $dqn = $request->dqn;
        $engine_number = $request->engine_number; // əvvəl: motor_no

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
        if (!empty($route_number)) {
            $query->where('route_number', 'ILIKE', "%{$route_number}%");
        }
        if (!empty($dqn)) {
            $query->where('dqn', 'ILIKE', "%{$dqn}%");
        }
        if (!empty($engine_number)) {
            $query->where('engine_number', 'ILIKE', "%{$engine_number}%");
        }

        $buses = $query->orderBy('id', 'desc')->paginate(config('settings.pagination', 15));
        $isEmpty = $buses->isEmpty();

        if ($request->ajax()) {
            return view('buses.partials.table', compact('buses', 'isEmpty'))->render();
        }

        return view('buses.index', compact('buses'));
    }

    public function show($id)
    {
        $bus = Bus::findOrFail($id);
        return view('buses.show', compact('bus'));
    }

    public function create()
    {
        return view('buses.create');
    }

    public function store(BusStoreRequest $request)
    {
        $this->authorize('create', Bus::class);
        $data = $request->validated();
        $data['date'] = now()->format('Y-m-d'); // əvvəl: tarix
        $data = $this->addGarageContext($data);
        Bus::create($data);
        return redirect()->route('buses.index')->with('success', 'Avtobus uğurla əlavə edildi!');
    }

    public function edit($id)
    {
        $bus = Bus::findOrFail($id);
        return view('buses.edit', compact('bus'));
    }

    public function update(BusUpdateRequest $request, $id)
    {
        $bus = Bus::findOrFail($id);
        $this->authorize('update', $bus);
        $data = $request->validated();
        $bus->update($data);
        return redirect()->route('buses.index')->with('success', 'Avtobus uğurla yeniləndi!');
    }

    public function destroy($id)
    {
        $bus = Bus::findOrFail($id);
        $bus->delete();
        return redirect()->route('buses.index')->with('success', 'Avtobus uğurla silindi!');
    }

    public function importForm()
    {
        return view('buses.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\BusesImport, $request->file('file'));
            return redirect()->route('buses.index')->with('success', 'Avtobuslar uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('buses.index')->with('error', 'İdxal zamanı xəta baş verdi. Faylın formatını yoxlayın və yenidən cəhd edin.');
        }
    }
}
