<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Imports\EmployeesImport;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function index()
    {
        // 🔥 DƏYİŞİKLİK: paginate(30) əlavə edildi
        $employees = Employee::orderBy('ad')
        ->paginate(config('settings.pagination', 25));
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        // BURADA İSTİFADƏ OLUNUR!
        $positions = config('settings.employee_positions');
        return view('employees.create', compact('positions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'soyad' => 'required|string|max:255',
            'vezifesi' => 'required|string|max:255',
            'qeyd' => 'nullable|string',
            'aktiv' => 'nullable|boolean', // ✅ ƏLAVƏ
        ]);

        // ✅ ƏLAVƏ: checkbox-dan gələn dəyəri boolean-a çevir
        $validated['aktiv'] = $request->boolean('aktiv');

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'İşçi uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $employee = Employee::findOrFail($id);
        return view('employees.show', compact('employee'));
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);

        // BURADA İSTİFADƏ OLUNUR!
        $positions = config('settings.employee_positions');
        return view('employees.edit', compact('employee', 'positions'));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'soyad' => 'required|string|max:255',
            'vezifesi' => 'required|string|max:255',
            'qeyd' => 'nullable|string',
            'aktiv' => 'nullable|boolean', // ✅ ƏLAVƏ
        ]);

        // ✅ ƏLAVƏ: checkbox-dan gələn dəyəri boolean-a çevir
        $validated['aktiv'] = $request->boolean('aktiv');

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'İşçi uğurla yeniləndi!');
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'İşçi uğurla silindi!');
    }

    public function importForm()
    {
        return view('employees.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            Excel::import(new EmployeesImport, $request->file('file'));
            return redirect()->route('employees.index')->with('success', 'İşçilər uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('employees.index')->with('error', 'İşçi idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }
}
