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

        // Same canonical path as search() — keeps pagination links
        // stable regardless of which route rendered the list.
        $complaints->setPath(route('complaints.index'));

        return view('complaints.index', compact('complaints'));
    }

    /**
     * Live search / filter — used by the AJAX search panel.
     *
     * Returns the partial view when the request comes from the
     * front-end (X-Requested-With: XMLHttpRequest), and falls back
     * to the full index view otherwise. This makes the same route
     * work for both AJAX calls and direct URL visits.
     *
     * NOTE: the paginator's path is explicitly set to the index route
     * so that pagination links rendered inside the partial point at
     * /complaints?page=N&filters — not /complaints/search?... The AJAX
     * handler intercepts clicks, but the URL must still be correct for
     * a JS-disabled browser or a middle-click "open in new tab".
     */
    public function search(Request $request): View|string
    {
        $this->authorize('viewAny', Complaint::class);

        $complaints = $this->buildFilteredQuery($request)
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15))
            ->withQueryString();

        // Point the paginator at the canonical index URL.
        $complaints->setPath(route('complaints.index'));

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

        // ────────────────────────────────────────────────────────
        // Soft-deleted employee guard
        // ────────────────────────────────────────────────────────
        // A complaint detail is a historical record: if the employee
        // who performed the work later leaves the company (and is
        // soft-deleted), their name must still appear on past
        // complaint cards. ComplaintPdfService::generate() already
        // resolves soft-deleted employees; the show page must match
        // so the on-screen card and the printed PDF agree.
        //
        // HasGarageScope remains active — cross-tenant references
        // are still filtered out.
        // ────────────────────────────────────────────────────────
        $employeesById = Employee::withTrashed()
            ->whereIn(
                'id',
                $complaint->details->pluck('employee_id')->filter()->unique()
            )
            ->get()
            ->keyBy('id');

        return view('complaints.show', compact('complaint', 'employeesById'));
    }

    public function edit(int $id): View
    {
        $complaint = Complaint::with(['items', 'details.employee', 'serviceVehicle'])
            ->findOrFail($id);

        $this->authorize('update', $complaint);

        // ────────────────────────────────────────────────────────
        // Reference data for the shared form partial.
        //
        // The partial expects the same variable names the create()
        // method uses, so both views can `@include` the same
        // `complaints.partials.form` without branching.
        // ────────────────────────────────────────────────────────
        $buses = Bus::orderBy('route_number')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();
        $drivers = Driver::active()->orderBy('code')->get();
        $serviceVehicles = ServiceVehicle::active()->orderBy('name')->get();

        // ────────────────────────────────────────────────────────
        // Shape the existing details into the same array format the
        // create form expects. Each row carries BOTH the raw
        // employee_id (used as a hidden input) and the resolved
        // employee_code / employee_name (rendered as visible inputs).
        //
        // `$detail->employee` uses withTrashed() on the relation, so a
        // soft-deleted employee still renders correctly on historical
        // cards — matching ComplaintPdfService::generate().
        // ────────────────────────────────────────────────────────
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

        return view('complaints.edit', compact(
            'complaint',
            'buses',
            'complaintTypes',
            'details',
            'employees',
            'drivers',
            'serviceVehicles',
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

    /**
     * Bulk soft-delete ALL complaints matching the current filter.
     *
     * Memory-safe: the ID stream is pulled from the database in
     * fixed-size chunks via ComplaintService::bulkDeleteByQuery().
     *
     * If a chunk fails partway through, the earlier chunks stay
     * committed and the controller reports the partial count with an
     * explicit warning — otherwise the operator would see a clean
     * "N deleted" success message while a large remainder was left
     * untouched.
     */
    public function bulkDeleteAll(Request $request): RedirectResponse
    {
        $this->authorize('delete', Complaint::class);

        @set_time_limit(300);

        $result = $this->complaintService->bulkDeleteByQuery(
            $this->buildFilteredQuery($request)
        );

        if ($result['deleted'] === 0) {
            return redirect()
                ->route('complaints.index')
                ->with('error', __('messages.flash.none_selected'));
        }

        // Partial failure: report both the count and the fact that the
        // operation stopped early.
        if ($result['error'] !== null) {
            return redirect()
                ->route('complaints.index')
                ->with('warning', __('messages.flash.bulk_delete_partial', [
                    'count' => $result['deleted'],
                    'items' => 'cards',
                ]));
        }

        return redirect()
            ->route('complaints.index')
            ->with('success', __('messages.flash.bulk_deleted', [
                'count' => $result['deleted'],
                'items' => 'cards',
            ]));
    }

    /**
     * Bulk soft-delete an explicit list of complaints (checkbox selection).
     *
     * The browser submits the selected IDs as a JSON-encoded string;
     * normalizeIds() parses and sanitizes it before the service is
     * called. Use bulkDeleteAll() instead when the intent is "delete
     * everything matching the current filter".
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $this->authorize('delete', Complaint::class);

        $ids = $this->normalizeIds($request->input('ids', []));

        if (empty($ids)) {
            return redirect()
                ->route('complaints.index')
                ->with('error', __('messages.flash.none_selected'));
        }

        $count = $this->complaintService->bulkDelete($ids);

        return redirect()
            ->route('complaints.index')
            ->with('success', __('messages.flash.bulk_deleted', [
                'count' => $count,
                'items' => 'cards',
            ]));
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

        try {
            if (! $this->pdfService->exists($complaint)) {
                $this->pdfService->save($complaint);
            }
        } catch (\Throwable $e) {
            // PDF generation is best-effort. DomPDF, disk-full and
            // permission failures all surface here. Log with context
            // so operators can diagnose, then return a friendly
            // message instead of a raw 500 stack trace.
            \Log::error('Complaint PDF generation failed', [
                'complaint_id' => $complaint->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
            ]);

            abort(500, __('messages.flash.pdf_generation_failed'));
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
     * Normalize an array of IDs coming from the bulk-selection form.
     *
     * Handles both JSON strings (submitted by the browser) and plain
     * arrays, and strips anything that is not a positive integer.
     *
     * @return array<int>
     */
    private function normalizeIds(mixed $ids): array
    {
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter(
            array_map('intval', $ids),
            fn (int $id) => $id > 0
        ));
    }
}
