<?php

namespace App\Http\Controllers;

use App\Models\MotorOilDetail;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MotorOilImport;

class MotorOilController extends Controller
{
    public function index()
    {
        // ✅ detal_adi → part_name
        $details = MotorOilDetail::orderBy('km')->orderBy('part_name')->get();
        $grouped = $details->groupBy('km');

        return view('motor-oil.index', compact('grouped'));
    }

    public function search(Request $request)
    {
        $search = preg_replace('/[^\d]/', '', (string) $request->search);

        $details = MotorOilDetail::when($search, function ($query, $search) {
            return $query->where('km', (int) $search);
        })
        ->orderBy('km')
        ->orderBy('part_name') // ✅ detal_adi → part_name
        ->get();

        $grouped = $details->groupBy('km');

        return view('motor-oil.partials.table', compact('grouped', 'search'));
    }

    public function importForm()
    {
        return view('motor-oil.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            Excel::import(new MotorOilImport, $request->file('file'));
            return redirect()->route('motor-oil.index')->with('success', 'Motor yağ detalları uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'Motor yağı idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }
}
