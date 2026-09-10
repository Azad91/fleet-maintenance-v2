<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComplaintCloseRequest;
use App\Http\Requests\ComplaintStoreRequest;
use App\Http\Requests\ComplaintUpdateRequest;
use App\Imports\ComplaintsImport;
use App\Models\Bus;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\Driver;
use App\Models\Employee;
use App\Services\Complaint\ComplaintPdfService;
use App\Services\Complaint\ComplaintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService,
        protected ComplaintPdfService $pdfService
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Complaint::class);

        $complaints = Complaint::with(['bus', 'items'])
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('complaints.index', compact('complaints'));
    }

    public function create(): View
    {
        $this->authorize('create', Complaint::class);

        $buses = Bus::orderBy('route_number')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();
        $drivers = Driver::active()->orderBy('code')->get();

        return view('complaints.create', compact('buses', 'complaintTypes', 'employees', 'drivers'));
    }

    public function store(ComplaintStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Complaint::class);

        $data = $request->validated();

        $complaint = $this->complaintService->create(
            $data,
            $request->input('detallar', []),
            $request->input('shikayet', [])
        );

        return redirect()
            ->route('complaints.show', $complaint)
            ->with('success', 'Kart uğurla açıldı. PDF formatında çap edə bilərsiniz.');
    }

    public function show(int $id): View
    {
        $complaint = Complaint::with(['bus', 'items', 'details.employee'])->findOrFail($id);

        $this->authorize('view', $complaint);

        $employeesById = Employee::whereIn(
            'id',
            $complaint->details->pluck('employee_id')->filter()->unique()
        )->get()->keyBy('id');

        return view('complaints.show', compact('complaint', 'employeesById'));
    }

    public function edit(int $id): View
    {
        $complaint = Complaint::with(['items', 'details'])->findOrFail($id);

        $this->authorize('update', $complaint);

        $buses = Bus::orderBy('route_number')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();
        $drivers = Driver::active()->orderBy('code')->get();

        $detallar = $complaint->details->map(function ($detail) {
            return [
                'shikayet_index' => $detail->shikayet_index,
                'code'           => $detail->code,
                'name'           => $detail->name,
                'stock_quantity' => $detail->stock_quantity,
                'used_quantity'  => $detail->used_quantity,
                'employee_id'    => $detail->employee_id,
                'notes'          => $detail->notes,
            ];
        })->toArray();

        $shikayetler = $complaint->items->pluck('description')->toArray();

        return view('complaints.edit', compact(
            'complaint',
            'buses',
            'complaintTypes',
            'detallar',
            'employees',
            'drivers',
            'shikayetler'
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
            $request->input('detallar', []),
            $request->input('shikayet', [])
        );

        // ✅ DÜZƏLİŞ: hardcoded '/complaints' → named route
        return redirect()
            ->route('complaints.index')
            ->with('success', 'Şikayət uğurla yeniləndi!');
    }

    public function destroy(int $id): RedirectResponse
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('delete', $complaint);

        $this->complaintService->delete($complaint);

        return redirect()
            ->route('complaints.index')
            ->with('success', 'Şikayət uğurla silindi! Anbar yeniləndi.');
    }

    public function close(ComplaintCloseRequest $request, int $id): RedirectResponse
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('close', $complaint);

        if ($complaint->status === 'həll olundu') {
            return back()->with('error', 'Bu şikayət artıq bağlanıb!');
        }

        $this->complaintService->close($complaint, $request->validated());

        try {
            $this->pdfService->save($complaint);
        } catch (\Throwable $e) {
            Log::error('PDF yaradılmadı', [
                'complaint_id' => $complaint->id,
                'error'        => $e->getMessage(),
                'request_id'   => \Illuminate\Support\Facades\Context::get('request_id'),
            ]);
        }

        return redirect()
            ->route('complaints.index')
            ->with('success', '✅ Şikayət bağlandı! Akt PDF olaraq yaradıldı.');
    }

    public function downloadPdf(int $id): BinaryFileResponse
    {
        $complaint = Complaint::with(['bus', 'details.employee'])->findOrFail($id);

        $this->authorize('view', $complaint);

        if (! $this->pdfService->exists($complaint)) {
            $this->pdfService->save($complaint);
        }

        $filePath = $this->pdfService->getFilePath($complaint);

        if (! file_exists($filePath)) {
            abort(404, 'PDF faylı tapılmadı.');
        }

        return response()->download($filePath, "is-karti-{$complaint->id}.pdf", [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="is-karti-'.$complaint->id.'.pdf"',
        ]);
    }

    public function importForm(): View
    {
        $this->authorize('import', Complaint::class);

        return view('complaints.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('import', Complaint::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new ComplaintsImport(
                (int) session('current_garage_id'),
                session('current_company_id') ? (int) session('current_company_id') : null
            );

            Excel::import($import, $request->file('file'));

            $skipped = $import->skipped;
            $failures = $import->failures();
            $imported = $import->importedCount;

            if (empty($skipped) && $failures->isEmpty()) {
                return redirect()
                    ->route('complaints.index')
                    ->with('success', "✅ {$imported} şikayət uğurla idxal edildi.");
            }

            $report = $this->buildImportReport($imported, $skipped, $failures);

            return redirect()
                ->route('complaints.index')
                ->with('warning', '⚠️ İdxal tamamlandı, lakin bəzi sətirlər atlandı.')
                ->with('import_report', $report);

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('complaints.index')
                ->with('error', 'İdxal zamanı gözlənilməz xəta baş verdi. Faylın formatını yoxlayın.');
        }
    }

    /**
     * İdxal nəticəsini strukturlaşdırılmış formada qurur.
     */
    private function buildImportReport(int $imported, array $skipped, $failures): array
    {
        $report = [
            'imported' => $imported,
            'skipped'  => [],
            'failed'   => [],
        ];

        foreach ($skipped as $row) {
            $report['skipped'][] = [
                'row'    => $row['row'],
                'dqn'    => $row['dqn'],
                'reason' => $row['reason'],
            ];
        }

        foreach ($failures as $failure) {
            $values = $failure->values();
            $report['failed'][] = [
                'row'    => $failure->row(),
                'dqn'    => $values['bus_dqn'] ?? $values['dqn'] ?? '—',
                'reason' => implode(', ', $failure->errors()),
            ];
        }

        return $report;
    }
}
