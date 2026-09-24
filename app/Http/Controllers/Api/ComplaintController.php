<?php

namespace App\Http\Controllers\Api;

use App\Actions\Complaints\CloseComplaintAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintStoreRequest;
use App\Http\Requests\ComplaintUpdateRequest;
use App\Models\Complaint;
use App\Services\Complaint\ComplaintPdfService;
use App\Services\Complaint\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService,
        protected ComplaintPdfService $pdfService,
        protected CloseComplaintAction $closeComplaintAction,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Complaint::class);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Complaint::with(['bus', 'items']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->bus_id) {
            $query->where('bus_id', $request->bus_id);
        }

        $complaints = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $complaints->items(),
            'meta' => [
                'total' => $complaints->total(),
                'per_page' => $complaints->perPage(),
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
            ],
        ]);
    }

    public function store(ComplaintStoreRequest $request)
    {
        Gate::authorize('create', Complaint::class);

        $data = $request->validated();
        $complaint = $this->complaintService->create(
            $data,
            $request->input('details', []),
            $request->input('complaints', [])
        );

        return response()->json([
            'message' => __('messages.flash.created', ['Item' => 'Card']),
            'data' => $complaint->load(['bus', 'items', 'details']),
        ], 201);
    }

    public function show(Complaint $complaint)
    {
        Gate::authorize('view', $complaint);

        return response()->json([
            'data' => $complaint->load(['bus', 'items', 'details.employee']),
        ]);
    }

    public function update(ComplaintUpdateRequest $request, Complaint $complaint)
    {
        Gate::authorize('update', $complaint);

        $data = $request->validated();
        $this->complaintService->update(
            $complaint,
            $data,
            $request->input('details', []),
            $request->input('complaints', [])
        );

        return response()->json([
            'message' => __('messages.flash.updated', ['Item' => 'Card']),
            'data' => $complaint->fresh()->load(['bus', 'items', 'details']),
        ]);
    }

    public function destroy(Complaint $complaint)
    {
        Gate::authorize('delete', $complaint);

        $this->complaintService->delete($complaint);

        return response()->json([
            'message' => __('messages.flash.deleted', ['Item' => 'Card']),
        ]);
    }

    public function close(Request $request, Complaint $complaint): JsonResponse
    {
        Gate::authorize('close', $complaint);

        $validated = $request->validate([
            'end_date' => 'required|date',
            'end_time' => 'required|date_format:H:i',
            'work_done' => 'required|string|min:5',
        ]);

        try {
            $closed = $this->closeComplaintAction->execute($complaint, $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first('status'),
            ], 422);
        }

        return response()->json([
            'message' => __('messages.flash.closed_success'),
            'data' => $closed,
        ]);
    }

    public function downloadPdf(Complaint $complaint)
    {
        Gate::authorize('view', $complaint);

        try {
            if (! $this->pdfService->exists($complaint)) {
                $this->pdfService->save($complaint);
            }
        } catch (\Throwable $e) {
            \Log::error('Complaint PDF generation failed', [
                'complaint_id' => $complaint->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
            ]);

            return response()->json([
                'message' => __('messages.flash.pdf_generation_failed'),
            ], 500);
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
}
