<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintStoreRequest;
use App\Http\Requests\ComplaintUpdateRequest;
use App\Models\Complaint;
use App\Services\Complaint\ComplaintPdfService;
use App\Services\Complaint\ComplaintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService,
        protected ComplaintPdfService $pdfService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Complaint::class);

        $query = Complaint::with(['bus', 'items']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->bus_id) {
            $query->where('bus_id', $request->bus_id);
        }

        $complaints = $query->orderBy('id', 'desc')
            ->paginate($request->per_page ?? 15);

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
            $request->input('detallar', []),
            $request->input('shikayet', [])
        );

        return response()->json([
            'message' => 'Kart uğurla açıldı.',
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
            $request->input('detallar', []),
            $request->input('shikayet', [])
        );

        return response()->json([
            'message' => 'Kart uğurla yeniləndi.',
            'data' => $complaint->fresh()->load(['bus', 'items', 'details']),
        ]);
    }

    public function destroy(Complaint $complaint)
    {
        Gate::authorize('delete', $complaint);

        $this->complaintService->delete($complaint);

        return response()->json([
            'message' => 'Kart uğurla silindi.',
        ]);
    }

    public function close(Request $request, Complaint $complaint)
    {
        Gate::authorize('close', $complaint);

        if ($complaint->status === 'həll olundu') {
            return response()->json([
                'message' => 'Bu şikayət artıq bağlanıb.',
            ], 422);
        }

        $request->validate([
            'end_date' => 'required|date',
            'end_time' => 'required|date_format:H:i',
            'work_done' => 'required|string|min:5',
        ]);

        $this->complaintService->close($complaint, $request->all());

        try {
            $this->pdfService->save($complaint);
        } catch (\Exception $e) {
            \Log::error('PDF yaradılmadı: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Şikayət bağlandı! PDF yaradıldı.',
            'data' => $complaint->fresh(),
        ]);
    }

    public function downloadPdf(Complaint $complaint)
    {
        Gate::authorize('view', $complaint);

        if (! $this->pdfService->exists($complaint)) {
            $this->pdfService->save($complaint);
        }

        $filePath = $this->pdfService->getFilePath($complaint);

        if (! file_exists($filePath)) {
            abort(404, 'PDF faylı tapılmadı.');
        }

        return response()->download($filePath, "is-karti-{$complaint->id}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="is-karti-'.$complaint->id.'.pdf"',
        ]);
    }
}
