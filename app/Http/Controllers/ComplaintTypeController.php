<?php

namespace App\Http\Controllers;

use App\Imports\ComplaintTypesImport;
use App\Models\ComplaintType;
use App\Services\GarageContext;
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
        // Resolve garage first — ComplaintTypePolicy::create() reads
        // the current garage via hasGarageRole(). Without this order
        // the user gets 403 instead of a clear redirect when no
        // garage is selected.
        if (! $this->hasResolvableGarage()) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('create', ComplaintType::class);

        return view('complaint-types.create');
    }

    public function store(Request $request)
    {
        if (! $this->hasResolvableGarage()) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('create', ComplaintType::class);

        $garageId = GarageContext::resolveGarageId();

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
        if (! $this->hasResolvableGarage()) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $type = ComplaintType::findOrFail($id);

        $this->authorize('update', $type);

        return view('complaint-types.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        if (! $this->hasResolvableGarage()) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $type = ComplaintType::findOrFail($id);

        $this->authorize('update', $type);

        $garageId = GarageContext::resolveGarageId();

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
        if (! $this->hasResolvableGarage()) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $type = ComplaintType::findOrFail($id);

        $this->authorize('delete', $type);

        $type->delete();

        return redirect()->route('complaint-types.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'Complaint type']));
    }

    public function importForm()
    {
        if (! $this->hasResolvableGarage()) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', ComplaintType::class);

        return view('complaint-types.import');
    }

    public function import(Request $request)
    {
        // Resolve garage first so that a missing context redirects to
        // garage selection instead of triggering a 403 in the policy.
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', ComplaintType::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(
                new ComplaintTypesImport(
                    $garageId,
                    GarageContext::resolveCompanyId(),
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

    /**
     * True when a garage can be resolved from the current request
     * context. Used as a precondition before calling authorize() so
     * that the user is redirected to garage selection when no garage
     * is active, instead of receiving a confusing 403.
     */
    private function hasResolvableGarage(): bool
    {
        $garageId = GarageContext::resolveGarageId();

        return $garageId !== null && $garageId > 0;
    }
}
