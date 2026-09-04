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
        $complaints = Complaint::with(['bus', 'items']) // ✅ items əlavə edildi
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));
        return view('complaints.index', compact('complaints'));
    }

    public function create()
    {
        $buses = Bus::orderBy('route_number')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();
        $drivers = Driver::active()->orderBy('code')->get();

        return view('complaints.create', compact('buses', 'complaintTypes', 'employees', 'drivers'));
    }

    public function store(ComplaintStoreRequest $request)
    {
        $data = $request->validated();

        // ✅ indi service-ə sadəcə items göndərilir
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
        // ✅ items və details əlaqələri əlavə edildi
        $complaint = Complaint::with(['bus', 'items', 'details.employee'])->findOrFail($id);

        $employeesById = Employee::whereIn(
            'id',
            $complaint->details->pluck('employee_id')->filter()->unique()
        )->get()->keyBy('id');

        return view('complaints.show', compact('complaint', 'employeesById'));
    }

    public function edit($id)
    {
        // ✅ items və details əlavə edildi
        $complaint = Complaint::with(['items', 'details'])->findOrFail($id);
        $buses = Bus::orderBy('route_number')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();
        $drivers = Driver::active()->orderBy('code')->get();

        // ✅ detalları view üçün hazırla
        $detallar = $complaint->details->map(function ($detail) {
            return [
                'shikayet_index' => $detail->shikayet_index,
                'kodu' => $detail->code,
                'adi' => $detail->name,
                'depo_miqdari' => $detail->stock_quantity,
                'islenen_miqdar' => $detail->used_quantity,
                'employee_id' => $detail->employee_id,
                'qeyd' => $detail->notes,
            ];
        })->toArray();

        // ✅ items-i view üçün hazırla
        $shikayetler = $complaint->items->pluck('description')->toArray();

        return view('complaints.edit', compact(
            'complaint', 'buses', 'complaintTypes', 'detallar', 'employees', 'drivers', 'shikayetler'
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
            'end_date' => 'required|date',      // əvvəl: is_bitme_tarix
            'end_time' => 'required|date_format:H:i',  // əvvəl: is_bitme_saat
            'work_done' => 'required|string|min:5',    // əvvəl: gorulen_is
        ]);

        $this->complaintService->close($complaint, $request->all());

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
        $complaint = Complaint::with(['bus', 'details.employee'])->findOrFail($id);
        $pdf = $this->pdfService->generate($complaint);
        return $pdf->stream("is-karti-{$complaint->id}.pdf");
    }

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
