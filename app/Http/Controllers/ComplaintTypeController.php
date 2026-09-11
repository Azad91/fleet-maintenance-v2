<?php

namespace App\Http\Controllers;

use App\Imports\ComplaintTypesImport;
use App\Models\ComplaintType;
use App\Models\Garage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ComplaintTypeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ComplaintType::class);

        $types = ComplaintType::orderBy('id')->get();

        return view('complaint-types.index', compact('types'));
    }

    public function create()
    {
        $this->authorize('create', ComplaintType::class);

        return view('complaint-types.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', ComplaintType::class);

        $garageId = Garage::getCurrentId();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('complaint_types', 'name')
                    ->where('garage_id', $garageId),
            ],
        ]);

        ComplaintType::create($validated);

        return redirect()->route('complaint-types.index')
            ->with('success', __('messages.flash.created', ['Item' => 'Complaint type']));
    }

    public function edit($id)
    {
        $type = ComplaintType::findOrFail($id);

        $this->authorize('update', $type);

        return view('complaint-types.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        $type = ComplaintType::findOrFail($id);

        $this->authorize('update', $type);

        $garageId = Garage::getCurrentId();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('complaint_types', 'name')
                    ->where('garage_id', $garageId)
                    ->ignore($type->id),
            ],
        ]);

        $type->update($validated);

        return redirect()->route('complaint-types.index')
            ->with('success', __('messages.flash.updated', ['Item' => 'Complaint type']));
    }

    public function destroy($id)
    {
        $type = ComplaintType::findOrFail($id);

        $this->authorize('delete', $type);

        $type->delete();

        return redirect()->route('complaint-types.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'Complaint type']));
    }

    public function importForm()
    {
        $this->authorize('import', ComplaintType::class);

        return view('complaint-types.import');
    }

    public function import(Request $request)
    {
        $this->authorize('import', ComplaintType::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(
                new ComplaintTypesImport(
                    (int) Garage::getCurrentId(),
                    Garage::getCurrentCompanyId() ? (int) Garage::getCurrentCompanyId() : null
                ),
                $request->file('file')
            );

            return redirect()->route('complaint-types.index')
                ->with('success', __('messages.flash.import_success', [
                    'count' => '',
                    'items' => 'Complaint types',
                ]));
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('complaint-types.index')
                ->with('error', __('messages.flash.import_error'));
        }
    }
}
