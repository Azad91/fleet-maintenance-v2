<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComplaintStoreRequest;
use App\Http\Requests\ComplaintUpdateRequest;
use App\Http\Requests\ComplaintCloseRequest;
use App\Imports\ComplaintsImport;
use App\Models\Bus;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\Driver;
use App\Models\Employee;
use App\Services\Complaint\ComplaintPdfService;
use App\Services\Complaint\ComplaintService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService,
        protected ComplaintPdfService $pdfService
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Complaint::class);  // ✅ ƏLAVƏ

        $complaints = Complaint::with(['bus', 'items'])
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('complaints.index', compact('complaints'));
    }

    public function create()
    {
        $this->authorize('create', Complaint::class);  // ✅ ƏLAVƏ

        $buses = Bus::orderBy('route_number')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();
        $drivers = Driver::active()->orderBy('code')->get();

        return view('complaints.create', compact('buses', 'complaintTypes', 'employees', 'drivers'));
    }

    public function store(ComplaintStoreRequest $request)
    {
        $this->authorize('create', Complaint::class);  // ✅ ƏLAVƏ

        $data = $request->validated();

        $complaint = $this->complaintService->create(
            $data,
            $request->input('detallar', []),
            $request->input('shikayet', [])
        );

        return redirect()->route('complaints.show', $complaint)
            ->with('success', 'Kart uğurla açıldı. PDF formatında çap edə bilərsiniz.');
    }

    public function show($id)
    {
        $complaint = Complaint::with(['bus', 'items', 'details.employee'])->findOrFail($id);

        $this->authorize('view', $complaint);  // ✅ ƏLAVƏ

        $employeesById = Employee::whereIn(
            'id',
            $complaint->details->pluck('employee_id')->filter()->unique()
        )->get()->keyBy('id');

        return view('complaints.show', compact('complaint', 'employeesById'));
    }

    public function edit($id)
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
                'code' => $detail->code,
                'name' => $detail->name,
                'stock_quantity' => $detail->stock_quantity,
                'used_quantity' => $detail->used_quantity,
                'employee_id' => $detail->employee_id,
                'notes' => $detail->notes,
            ];
        })->toArray();

        // ✅ Burada düzgün istifadə olunur
        $shikayetler = $complaint->items->pluck('description')->toArray();

        return view('complaints.edit', compact(
            'complaint', 'buses', 'complaintTypes', 'detallar', 'employees', 'drivers', 'shikayetler'
        ));
    }

    public function update(ComplaintUpdateRequest $request, $id)
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('update', $complaint);  // ✅ ƏLAVƏ

        $data = $request->validated();

        $this->complaintService->update(
            $complaint,
            $data,
            $request->input('detallar', []),
            $request->input('shikayet', [])
        );

        return redirect('/complaints')->with('success', 'Şikayət uğurla yeniləndi!');
    }

    public function destroy($id)
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('delete', $complaint);  // ✅ ƏLAVƏ

        $this->complaintService->delete($complaint);

        return redirect()->route('complaints.index')
            ->with('success', 'Şikayət uğurla silindi! Anbar yeniləndi.');
    }

    public function close(ComplaintCloseRequest $request, $id)
    {
        $complaint = Complaint::findOrFail($id);
        $this->authorize('close', $complaint);

        if ($complaint->status === 'həll olundu') {
            return back()->with('error', 'Bu şikayət artıq bağlanıb!');
        }

        $this->complaintService->close($complaint, $request->validated());

        try {
            $this->pdfService->save($complaint);
        } catch (\Exception $e) {
            \Log::error('PDF yaradılmadı: '.$e->getMessage());
        }

        return redirect()->route('complaints.index')
            ->with('success', '✅ Şikayət bağlandı! Akt PDF olaraq yaradıldı.');
    }

    public function downloadPdf($id)
    {
        $complaint = Complaint::with(['bus', 'details.employee'])->findOrFail($id);

        $this->authorize('view', $complaint);  // ✅ ƏLAVƏ

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

    public function importForm()
    {
        $this->authorize('import', Complaint::class);  // ✅ ƏLAVƏ

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

            // Uğurlu idxal, heç bir problem yoxdur
            if (empty($skipped) && $failures->isEmpty()) {
                return redirect()->route('complaints.index')
                    ->with('success', "✅ {$imported} şikayət uğurla idxal edildi.");
            }

            // Bəzi sətirlər atlandı — istifadəçiyə göstər
            $report = $this->buildImportReport($imported, $skipped, $failures);

            return redirect()->route('complaints.index')
                ->with('warning', "⚠️ İdxal tamamlandı, lakin bəzi sətirlər atlandı.")
                ->with('import_report', $report);

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('complaints.index')
                ->with('error', 'İdxal zamanı gözlənilməz xəta baş verdi. Faylın formatını yoxlayın.');
        }
    }

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
            $errors = implode(', ', $failure->errors());
            $report['failed'][] = [
                'row'    => $failure->row(),
                'dqn'    => $failure->values()['bus_dqn'] ?? $failure->values()['dqn'] ?? '—',
                'reason' => $errors,
            ];
        }

        return $report;
    }
}
