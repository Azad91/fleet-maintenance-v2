<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeStoreRequest;
use App\Http\Requests\EmployeeUpdateRequest;
use App\Imports\EmployeesImport;
use App\Models\Employee;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Employee::class);  // ✅ ƏLAVƏ

        $employees = Employee::orderBy('first_name')->paginate(config('settings.pagination', 30));

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $this->authorize('create', Employee::class);  // ✅ ƏLAVƏ

        $positions = config('settings.employee_positions');

        return view('employees.create', compact('positions'));
    }

    public function store(EmployeeStoreRequest $request)
    {
        $this->authorize('create', Employee::class);

        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'İşçi uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $employee = Employee::findOrFail($id);

        $this->authorize('view', $employee);  // ✅ ƏLAVƏ

        return view('employees.show', compact('employee'));
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);

        $this->authorize('update', $employee);  // ✅ ƏLAVƏ

        $positions = config('settings.employee_positions');

        return view('employees.edit', compact('employee', 'positions'));
    }

    public function update(EmployeeUpdateRequest $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $this->authorize('update', $employee);

        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'İşçi uğurla yeniləndi!');
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        $this->authorize('delete', $employee);  // ✅ ƏLAVƏ

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'İşçi uğurla silindi!');
    }

    public function importForm()
    {
        $this->authorize('import', Employee::class);  // ✅ ƏLAVƏ

        return view('employees.import');
    }

    public function import(Request $request)
    {
        $this->authorize('import', Employee::class);  // ✅ ƏLAVƏ

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(
                new EmployeesImport(
                    (int) session('current_garage_id'),
                    session('current_company_id') ? (int) session('current_company_id') : null
                ),
                $request->file('file')
            );

            return redirect()->route('employees.index')->with('success', 'İşçilər uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('employees.index')->with('error', 'İşçi idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }
}
