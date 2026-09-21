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

        // Only set is_active when the form actually submitted it.
        // Otherwise the DB column default (true) applies — matching
        // the front-end intent where the checkbox is checked by
        // default and an unchecked submission explicitly sends "0".
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

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

        // Same reasoning as store(): only touch is_active when the
        // form submitted it, so a partial update does not silently
        // flip the flag.
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

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
        // Resolve garage first so that a missing context redirects to
        // garage selection instead of triggering a 403 in the policy.
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', Employee::class);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $import = new EmployeesImport(
                $garageId,
                GarageContext::resolveCompanyId(),
            );

            Excel::import($import, $request->file('file'));

            $skipped = $import->skipped;
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
