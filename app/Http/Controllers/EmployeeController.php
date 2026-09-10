<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeStoreRequest;
use App\Http\Requests\EmployeeUpdateRequest;
use App\Imports\EmployeesImport;
use App\Models\Employee;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::orderBy('first_name')
            ->paginate(config('settings.pagination', 30));

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $this->authorize('create', Employee::class);

        $positions = config('settings.employee_positions');

        return view('employees.create', compact('positions'));
    }

    public function store(EmployeeStoreRequest $request)
    {
        $this->authorize('create', Employee::class);

        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        Employee::create($validated);

        return redirect()->route('employees.index')
            ->with('success', __('messages.flash.created', ['Item' => 'Employee']));
    }

    public function show($id)
    {
        $employee = Employee::findOrFail($id);

        $this->authorize('view', $employee);

        return view('employees.show', compact('employee'));
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);

        $this->authorize('update', $employee);

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

        return redirect()->route('employees.index')
            ->with('success', __('messages.flash.updated', ['Item' => 'Employee']));
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        $this->authorize('delete', $employee);

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'Employee']));
    }

    public function importForm()
    {
        $this->authorize('import', Employee::class);

        return view('employees.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('import', Employee::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new EmployeesImport(
                (int) GarageContext::getGarageId(),
                GarageContext::getCompanyId() ? (int) GarageContext::getCompanyId() : null
            );

            Excel::import($import, $request->file('file'));

            $skipped  = $import->skipped;
            $imported = $import->importedCount;

            if (empty($skipped)) {
                return redirect()->route('employees.index')
                    ->with('success', __('messages.flash.import_success', [
                        'count' => $imported,
                        'items' => 'employees',
                    ]));
            }

            return redirect()->route('employees.index')
                ->with('warning', __('messages.flash.import_partial'))
                ->with('import_report', $this->buildImportReport($imported, $skipped, collect()));

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('employees.index')
                ->with('error', __('messages.flash.import_error'));
        }
    }
}
