<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComplaintStoreRequest;
use App\Http\Requests\ComplaintUpdateRequest;
use App\Models\Complaint;
use App\Models\Bus;
use App\Models\ComplaintType;
use App\Models\Employee;
use App\Models\Driver;
use App\Services\Complaint\ComplaintService;
use App\Services\Complaint\ComplaintPdfService;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService,
        protected ComplaintPdfService $pdfService
    ) {}

    public function index()
    {
        $complaints = Complaint::with('bus')
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));
        return view('complaints.index', compact('complaints'));
    }

    public function create()
    {
        $buses = Bus::orderBy('xett_no')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('ad')->get();
        $drivers = Driver::active()->orderBy('kodu')->get();

        return view('complaints.create', compact('buses', 'complaintTypes', 'employees', 'drivers'));
    }

    public function store(ComplaintStoreRequest $request)
    {
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
        $complaint = Complaint::with('bus')->findOrFail($id);
        $employeesById = Employee::whereIn(
            'id',
            collect($complaint->detallar ?? [])->pluck('employee_id')->filter()->unique()
        )->get()->keyBy('id');

        if ($complaint->detallar) {
            $complaint->detallar = is_array($complaint->detallar)
                ? $complaint->detallar
                : json_decode($complaint->detallar, true);
        }

        return view('complaints.show', compact('complaint', 'employeesById'));
    }

    public function edit($id)
    {
        $complaint = Complaint::findOrFail($id);
        $buses = Bus::orderBy('xett_no')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('ad')->get();
        $drivers = Driver::active()->orderBy('kodu')->get();

        $detallar = [];
        if ($complaint->detallar) {
            $detallar = is_array($complaint->detallar)
                ? $complaint->detallar
                : json_decode($complaint->detallar, true);
        }

        return view('complaints.edit', compact(
            'complaint', 'buses', 'complaintTypes', 'detallar', 'employees', 'drivers'
        ));
    }

    public function update(ComplaintUpdateRequest $request, $id)
    {
        $complaint = Complaint::findOrFail($id);
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
        $this->complaintService->delete($complaint);

        return redirect()->route('complaints.index')
            ->with('success', 'Şikayət uğurla silindi! Anbar yeniləndi.');
    }

    public function close(Request $request, $id)
    {
        $complaint = Complaint::findOrFail($id);

        if ($complaint->status === 'həll olundu') {
            return back()->with('error', 'Bu şikayət artıq bağlanıb!');
        }

        $request->validate([
            'is_bitme_tarix' => 'required|date',
            'is_bitme_saat' => 'required|date_format:H:i',
            'gorulen_is' => 'required|string|min:5',
        ]);

        $this->complaintService->close($complaint, $request->all());

        // PDF yarat
        try {
            $this->pdfService->save($complaint);
        } catch (\Exception $e) {
            \Log::error('PDF yaradılmadı: ' . $e->getMessage());
        }

        return redirect()->route('complaints.index')
            ->with('success', '✅ Şikayət bağlandı! Akt PDF olaraq yaradıldı.');
    }

    public function downloadPdf($id)
    {
        $complaint = Complaint::with('bus')->findOrFail($id);
        $pdf = $this->pdfService->generate($complaint);
        return $pdf->stream("is-karti-{$complaint->id}.pdf");
    }

    // =============== IMPORT ===============
    public function importForm()
    {
        return view('complaints.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(
                new \App\Imports\ComplaintsImport(),
                $request->file('file')
            );
            return redirect()->route('complaints.index')
                ->with('success', 'Şikayətlər uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('complaints.index')
                ->with('error', 'Şikayət idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }
}
