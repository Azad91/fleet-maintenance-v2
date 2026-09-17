<?php

namespace App\Http\Controllers;

use App\Actions\Complaints\CloseComplaintAction;
use App\Http\Requests\ComplaintCloseRequest;
use App\Http\Requests\ComplaintStoreRequest;
use App\Http\Requests\ComplaintUpdateRequest;
use App\Imports\ComplaintsImport;
use App\Models\Bus;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\ServiceVehicle;
use App\Services\Complaint\ComplaintPdfService;
use App\Services\Complaint\ComplaintService;
use App\Services\GarageContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService,
        protected ComplaintPdfService $pdfService,
        protected CloseComplaintAction $closeComplaintAction,
    ) {}

    /**
     * List complaints with optional filters.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Complaint::class);

        $complaints = $this->buildFilteredQuery($request)
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15))
            ->withQueryString();

        return view('complaints.index', compact('complaints'));
    }

    /**
     * Live search / filter — used by the AJAX search panel.
     *
     * Returns the partial view when the request comes from the
     * front-end (X-Requested-With: XMLHttpRequest), and falls back
     * to the full index view otherwise. This makes the same route
     * work for both AJAX calls and direct URL visits.
     */
    public function search(Request $request): View|string
    {
        $this->authorize('viewAny', Complaint::class);

        $complaints = $this->buildFilteredQuery($request)
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15))
            ->withQueryString();

        if ($this->isAjaxRequest($request)) {
            return view('complaints.partials.table', compact('complaints'))->render();
        }

        return view('complaints.index', compact('complaints'));
    }

    public function create(): View
    {
        $this->authorize('create', Complaint::class);

        $buses = Bus::orderBy('route_number')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();
        $drivers = Driver::active()->orderBy('code')->get();
        $serviceVehicles = ServiceVehicle::active()->orderBy('name')->get();

        $complaint = new Complaint;

        return view('complaints.create', compact(
            'buses',
            'complaintTypes',
            'employees',
            'drivers',
            'serviceVehicles',
            'complaint'
        ));
    }

    public function store(ComplaintStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Complaint::class);

        $data = $request->validated();

        $complaint = $this->complaintService->create(
            $data,
            $request->input('details', []),
            $request->input('complaints', [])
        );

        return redirect()
            ->route('complaints.show', $complaint)
            ->with('success', __('messages.flash.created', ['Item' => 'Card']));
    }

    public function show(int $id): View
    {
        $complaint = Complaint::with(['bus', 'items', 'details.employee', 'serviceVehicle'])
            ->findOrFail($id);

        $this->authorize('view', $complaint);

        $employeesById = Employee::whereIn(
            'id',
            $complaint->details->pluck('employee_id')->filter()->unique()
        )->get()->keyBy('id');

        return view('complaints.show', compact('complaint', 'employeesById'));
    }

    public function edit(int $id): View
    {
        $complaint = Complaint::with(['items', 'details.employee', 'serviceVehicle'])
            ->findOrFail($id);

        $this->authorize('update', $complaint);

        $buses = Bus::orderBy('route_number')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();
        $drivers = Driver::active()->orderBy('code')->get();
        $serviceVehicles = ServiceVehicle::active()->orderBy('name')->get();

        $details = $complaint->details->map(function ($detail) {
            return [
                'shikayet_index' => $detail->shikayet_index,
                'code' => $detail->code,
                'name' => $detail->name,
                'stock_quantity' => $detail->stock_quantity,
                'used_quantity' => $detail->used_quantity,
                'employee_id' => $detail->employee_id,
                'employee_code' => $detail->employee?->code,
                'employee_name' => $detail->employee?->full_name_with_position,
                'notes' => $detail->notes,
            ];
        })->toArray();

        $complaints = $complaint->items->pluck('description')->toArray();

        return view('complaints.edit', compact(
            'complaint',
            'buses',
            'complaintTypes',
            'details',
            'employees',
            'drivers',
            'serviceVehicles',
            'complaints'
        ));
    }

    public function update(ComplaintUpdateRequest $request, int $id): RedirectResponse
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('update', $complaint);

        $data = $request->validated();

        $this->complaintService->update(
            $complaint,
            $data,
            $request->input('details', []),
            $request->input('complaints', [])
        );

        return redirect()
            ->route('complaints.index')
            ->with('success', __('messages.flash.updated', ['Item' => 'Card']));
    }

    public function destroy(int $id): RedirectResponse
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('delete', $complaint);

        $this->complaintService->delete($complaint);

        return redirect()
            ->route('complaints.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'Card']));
    }

    public function close(ComplaintCloseRequest $request, int $id): RedirectResponse
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('close', $complaint);

        try {
            $this->closeComplaintAction->execute($complaint, $request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first('status'));
        }

        return redirect()
            ->route('complaints.index')
            ->with('success', __('messages.flash.closed_success'));
    }

    public function downloadPdf(int $id): BinaryFileResponse
    {
        $complaint = Complaint::with(['bus', 'details.employee', 'serviceVehicle'])
            ->findOrFail($id);

        $this->authorize('view', $complaint);

        if (! $this->pdfService->exists($complaint)) {
            $this->pdfService->save($complaint);
        }

        $filePath = $this->pdfService->getFilePath($complaint);

        if (! file_exists($filePath)) {
            abort(404, __('messages.flash.pdf_not_found'));
        }

        return response()->download($filePath, "work-card-{$complaint->id}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="work-card-'.$complaint->id.'.pdf"',
        ]);
    }

    public function importForm(): View
    {
        $this->authorize('import', Complaint::class);

        return view('complaints.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $garageId = GarageContext::resolveGarageId();

        if ($garageId === null || $garageId <= 0) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_current_garage'));
        }

        $this->authorize('import', Complaint::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'historical' => 'nullable|boolean',
        ]);

        $deductStock = ! $request->boolean('historical');

        try {
            $import = new ComplaintsImport(
                $garageId,
                GarageContext::resolveCompanyId(),
                deductStock: $deductStock,
            );

            Excel::import($import, $request->file('file'));

            $skipped = $import->skipped;
            $failures = method_exists($import, 'failures') ? $import->failures() : collect();
            $imported = $import->importedCount;

            if (empty($skipped) && $failures->isEmpty()) {
                $message = $deductStock
                    ? __('messages.flash.import_success', [
                        'count' => $imported,
                        'items' => 'cards',
                    ])
                    : __('messages.complaints.import_success_historical', [
                        'count' => $imported,
                    ]);

                return redirect()->route('complaints.index')->with('success', $message);
            }

            return redirect()->route('complaints.index')
                ->with('warning', __('messages.flash.import_partial'))
                ->with('import_report', $this->buildImportReport($imported, $skipped, $failures));

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('complaints.index')
                ->with('error', __('messages.flash.import_error'));
        }
    }

    // ==================== HELPERS ====================

    /**
     * Build the complaint query with every filter applied.
     *
     * Shared between index() and search() so that the filter rules
     * stay in sync no matter how the list is requested.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        $query = Complaint::with(['bus', 'items', 'details', 'serviceVehicle']);

        // ─── Free-text search ───
        // Matches bus DQN, bus route, or any complaint item description.
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereHas('bus', function ($bq) use ($search) {
                    $bq->where('dqn', 'ILIKE', "%{$search}%")
                        ->orWhere('route_number', 'ILIKE', "%{$search}%");
                })->orWhereHas('items', function ($iq) use ($search) {
                    $iq->where('description', 'ILIKE', "%{$search}%");
                });
            });
        }

        // ─── Status ───
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ─── Complaint type ───
        if ($request->filled('complaint_type')) {
            $query->where('complaint_type', $request->complaint_type);
        }

        // ─── Location (road / garage) ───
        if ($request->filled('yer')) {
            $query->where('yer', $request->yer);
        }

        // ─── Date range ───
        // Prefer the operator-supplied work date. Falls back to
        // start_date, then created_at, using COALESCE at the SQL level
        // so the fallback is evaluated per row.
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $effectiveDate = 'COALESCE(reported_date, start_date, created_at::date)';

            if ($request->filled('date_from')) {
                $query->whereRaw("{$effectiveDate} >= ?", [$request->date_from]);
            }

            if ($request->filled('date_to')) {
                $query->whereRaw("{$effectiveDate} <= ?", [$request->date_to]);
            }
        }

        return $query;
    }

    /**
     * True when the request should receive a partial view instead of
     * the full page.
     */
    private function isAjaxRequest(Request $request): bool
    {
        return $request->header('X-Requested-With') === 'XMLHttpRequest'
            || $request->boolean('_ajax');
    }
}