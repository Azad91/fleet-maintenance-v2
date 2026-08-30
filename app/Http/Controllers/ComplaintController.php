<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComplaintStoreRequest;
use App\Http\Requests\ComplaintUpdateRequest;
use App\Models\Bus;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\ServiceTemplate;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ComplaintsImport;
use App\Models\Employee;
use App\Models\Driver;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class ComplaintController extends Controller
{
    public function index()
    {
        $complaints = Complaint::with('bus')
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));
        return view('complaints.index', compact('complaints'));
    }

    public function search(Request $request)
    {
        $dqn = $request->dqn;
        $xett_no = $request->xett_no;
        $yer = $request->yer;
        $shikayet = $request->shikayet;

        $complaints = Complaint::with('bus')
            ->when($dqn, function ($query, $dqn) {
                return $query->whereHas('bus', function ($q) use ($dqn) {
                    $q->where('dqn', 'ILIKE', "%{$dqn}%");
                });
            })
            ->when($xett_no, function ($query, $xett_no) {
                return $query->whereHas('bus', function ($q) use ($xett_no) {
                    $q->where('xett_no', 'ILIKE', "%{$xett_no}%");
                });
            })
            ->when($yer, function ($query, $yer) {
                return $query->where('yer', $yer);
            })
            ->when($shikayet, function ($query, $shikayet) {
                return $query->where('shikayet', 'ILIKE', "%{$shikayet}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('complaints.partials.table', compact('complaints', 'dqn', 'xett_no', 'yer', 'shikayet'));
    }

    public function create()
    {
        $buses = Bus::orderBy('xett_no')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $serviceTemplates = ServiceTemplate::orderBy('default_km_interval', 'asc')->get();
        $employees = Employee::active()->orderBy('ad')->get();
        $drivers = Driver::active()->orderBy('kodu')->get();

        return view('complaints.create', compact(
            'buses',
            'complaintTypes',
            'serviceTemplates',
            'employees',
            'drivers'
        ));
    }

    public function store(ComplaintStoreRequest $request)
    {
        $data = $request->validated();

        if (($data['yer'] ?? null) === 'yol') {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['surucu_adi'] = $driver->full_name;
        } else {
            $data['driver_id'] = null;
            $data['surucu_adi'] = null;
        }

        // employee_id - ni əlavə et
        if ($request->has('employee_id')) {
            $data['employee_id'] = $request->employee_id;
        }

        // Şikayət array - ni string - ə çevir
        if ($request->has('shikayet') && is_array($request->shikayet)) {
            $data['shikayet'] = implode("\n", array_filter($request->shikayet));
        }

        // Texniki xidmət məlumatlarını saxla
        if ($request->has('service_template_id')) {
            $data['service_template_id'] = $request->service_template_id;
        }
        if ($request->has('service_km')) {
            $data['service_km'] = $request->service_km;
        }

        // 🔥 KİM AÇDI?
        $data['created_by'] = auth()->id();

        $complaint = DB::transaction(function () use ($request, &$data) {
            // Detalları JSON olaraq saxla (anbar əməliyyatları ilə birlikdə)
            if ($request->has('detallar') && is_array($request->detallar)) {
                $detallar = [];
                foreach ($request->detallar as $detal) {
                    if (!empty($detal['kodu'])) {
                        $warehouse = Warehouse::where('kod', $detal['kodu'])->lockForUpdate()->first();

                        if (! $warehouse) {
                            throw ValidationException::withMessages([
                                'detallar' => "'{$detal['kodu']}' kodlu detal cari qarajın anbarında tapılmadı."
                            ]);
                        }

                        // 🔥 MƏNFİ STOK YOXLANIŞI
                        $usedQuantity = (int) ($detal['islenen_miqdar'] ?? 0);
                        if ($warehouse->miqdar < $usedQuantity) {
                            throw ValidationException::withMessages([
                                'detallar' => "Anbarda kifayət qədər '{$warehouse->ad}' yoxdur. (Tələb: {$usedQuantity}, Mövcud: {$warehouse->miqdar})"
                            ]);
                        }

                        $detallar[] = [
                            'shikayet_index' => $detal['shikayet_index'] ?? 0,
                            'kodu' => $detal['kodu'],
                            'adi' => $warehouse ? $warehouse->ad : null,
                            'depo_miqdari' => $warehouse ? $warehouse->miqdar : null,
                            'islenen_miqdar' => $detal['islenen_miqdar'] ?? 0,
                            'employee_id' => $detal['employee_id'] ?? null,
                            'qeyd' => $detal['qeyd'] ?? null,
                        ];

                        if (!empty($detal['islenen_miqdar']) && $detal['islenen_miqdar'] > 0) {
                            $warehouse->miqdar = $warehouse->miqdar - $detal['islenen_miqdar'];
                            $warehouse->save();
                        }
                    }
                }
                $data['detallar'] = $detallar;
            } else {
                $data['detallar'] = null;
            }

            $complaint = Complaint::create($data);
            $this->syncComplaintItems($complaint, $request->input('shikayet', []), $data['sikayet_tipi'] ?? null);

            return $complaint;
        });

        return redirect()->route('complaints.show', $complaint)->with('success', 'Kart uğurla açıldı. PDF formatında çap edə bilərsiniz.');
    }

    public function show($id)
    {
        $complaint = Complaint::with('bus')->findOrFail($id);
        $employeesById = Employee::whereIn(
            'id',
            collect($complaint->detallar ?? [])->pluck('employee_id')->filter()->unique()
        )->get()->keyBy('id');

        if ($complaint->detallar) {
            $complaint->detallar = is_array($complaint->detallar) ? $complaint->detallar : json_decode($complaint->detallar, true);
        }

        return view('complaints.show', compact('complaint', 'employeesById'));
    }

    public function edit($id)
    {
        $complaint = Complaint::findOrFail($id);
        $buses = Bus::orderBy('xett_no')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('ad')->get();
        $drivers = Driver::active()->orderBy('kodu')->get(); // ✅ ƏLAVƏ ET

        $detallar = [];
        if ($complaint->detallar) {
            $detallar = is_array($complaint->detallar) ? $complaint->detallar : json_decode($complaint->detallar, true);
        }

        return view('complaints.edit', compact(
            'complaint',
            'buses',
            'complaintTypes',
            'detallar',
            'employees',
            'drivers' // ✅ ƏLAVƏ ET
        ));
    }

    public function update(ComplaintUpdateRequest $request, $id)
    {
        $complaint = Complaint::findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $complaint) {
            // 1. Köhnə detalları al
            $oldDetallar = $complaint->detallar ?? [];
            if (is_string($oldDetallar)) {
                $oldDetallar = json_decode($oldDetallar, true) ?? [];
            }
            if (!is_array($oldDetallar)) {
                $oldDetallar = [];
            }

            // 2. Bütün sahələri yenilə
            $complaint->status = $validated['status'];
            $complaint->yer = $validated['yer'];
            $complaint->surucu_adi = $validated['surucu_adi'] ?? null;
            $complaint->km = $validated['km'] ?? null;
            $complaint->sikayet_tipi = $validated['sikayet_tipi'] ?? null;
            $complaint->bildirilme_tarix = $validated['bildirilme_tarix'] ?? null;
            $complaint->bildirilme_saat = $validated['bildirilme_saat'] ?? null;
            $complaint->is_baslama_tarix = $validated['is_baslama_tarix'] ?? null;
            $complaint->is_baslama_saat = $validated['is_baslama_saat'] ?? null;
            $complaint->is_bitme_tarix = $validated['is_bitme_tarix'] ?? null;
            $complaint->is_bitme_saat = $validated['is_bitme_saat'] ?? null;

            // 3. Şikayət mətnini yenilə
            if (!empty($validated['shikayet']) && is_array($validated['shikayet'])) {
                $complaint->shikayet = implode("\n", array_filter($validated['shikayet']));
                $this->syncComplaintItems($complaint, $validated['shikayet'], $validated['sikayet_tipi'] ?? null);
            }

            // 4. Köhnə detalları anbara geri qaytar
            foreach ($oldDetallar as $old) {
                if (!empty($old['kodu']) && !empty($old['islenen_miqdar'])) {
                    $warehouse = Warehouse::where('kod', $old['kodu'])->lockForUpdate()->first();
                    if ($warehouse) {
                        $warehouse->miqdar = $warehouse->miqdar + $old['islenen_miqdar'];
                        $warehouse->save();
                    }
                }
            }

            // 5. Yeni detalları tətbiq et (mənfi stok yoxlanışı ilə)
            if (!empty($validated['detallar']) && is_array($validated['detallar'])) {
                $detallar = [];
                foreach ($validated['detallar'] as $detal) {
                    if (!empty($detal['kodu'])) {
                        $warehouse = Warehouse::where('kod', $detal['kodu'])->lockForUpdate()->first();

                        if (! $warehouse) {
                            throw ValidationException::withMessages([
                                'detallar' => "'{$detal['kodu']}' kodlu detal cari qarajın anbarında tapılmadı."
                            ]);
                        }

                        $usedQuantity = (int) ($detal['islenen_miqdar'] ?? 0);
                        if ($warehouse->miqdar < $usedQuantity) {
                            throw ValidationException::withMessages([
                                'detallar' => "Anbarda kifayət qədər '{$warehouse->ad}' yoxdur. (Tələb: {$usedQuantity}, Mövcud: {$warehouse->miqdar})"
                            ]);
                        }

                        $detallar[] = [
                            'shikayet_index' => $detal['shikayet_index'] ?? 0,
                            'kodu' => $detal['kodu'],
                            'adi' => $warehouse ? $warehouse->ad : null,
                            'depo_miqdari' => $warehouse ? $warehouse->miqdar : null,
                            'islenen_miqdar' => $detal['islenen_miqdar'] ?? 0,
                            'employee_id' => $detal['employee_id'] ?? null,
                            'qeyd' => $detal['qeyd'] ?? null,
                        ];

                        if (!empty($detal['islenen_miqdar']) && $detal['islenen_miqdar'] > 0) {
                            $warehouse->miqdar = $warehouse->miqdar - $detal['islenen_miqdar'];
                            $warehouse->save();
                        }
                    }
                }
                $complaint->detallar = $detallar;
            } else {
                $complaint->detallar = null;
            }

            // 6. Service template sahələri
            if ($request->has('service_template_id')) {
                $complaint->service_template_id = $request->service_template_id;
            }
            if ($request->has('service_km')) {
                $complaint->service_km = $request->service_km;
            }

            // 7. ✅ DÜZƏLİŞ: driver_id əlavə et
            if (($validated['yer'] ?? null) === 'yol') {
                if (!empty($validated['driver_id'])) {
                    $driver = Driver::active()->findOrFail($validated['driver_id']);
                    $complaint->surucu_adi = $driver->full_name;
                    $complaint->driver_id = $driver->id;
                }
            } else {
                $complaint->driver_id = null;
                $complaint->surucu_adi = null;
            }

            $complaint->save();
        });

        return redirect('/complaints')->with('success', 'Şikayət uğurla yeniləndi!');
    }

    private function syncComplaintItems(Complaint $complaint, array $items, ?string $type): void
    {
        $complaint->items()->delete();

        foreach (array_filter(array_map('trim', $items)) as $description) {
            $complaint->items()->create([
                'description' => $description,
                'type' => $type,
            ]);
        }
    }

    // 🔥 DESTROY METODU (STOK GERİ QAYTARILIR)
    public function destroy($id)
    {
        $complaint = Complaint::findOrFail($id);

        DB::transaction(function () use ($complaint) {
            // 1. Silinən şikayətin detallarını geri qaytar
            $detallar = $complaint->detallar ?? [];
            if (is_string($detallar)) {
                $detallar = json_decode($detallar, true) ?? [];
            }

            if (is_array($detallar)) {
                foreach ($detallar as $detal) {
                    if (!empty($detal['kodu']) && !empty($detal['islenen_miqdar']) && $detal['islenen_miqdar'] > 0) {
                        $warehouse = Warehouse::where('kod', $detal['kodu'])->lockForUpdate()->first();
                        if ($warehouse) {
                            $warehouse->miqdar = $warehouse->miqdar + $detal['islenen_miqdar'];
                            $warehouse->save();
                        }
                    }
                }
            }

            // 2. Şikayəti sil
            $complaint->delete();
        });

        return redirect()->route('complaints.index')->with('success', 'Şikayət uğurla silindi! Anbar yeniləndi.');
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
            Excel::import(new ComplaintsImport, $request->file('file'));
            return redirect()->route('complaints.index')->with('success', 'Şikayətlər uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('complaints.index')->with('error', 'Şikayət idxalı zamanı xəta baş verdi. Faylı yoxlayıb yenidən cəhd edin.');
        }
    }

    // =============== BAĞLA (CLOSE) ===============
    public function close(Request $request, $id)
    {
        $complaint = Complaint::findOrFail($id);

        // Yalnız açıq şikayətlər bağlana bilər
        if ($complaint->status === 'həll olundu') {
            return back()->with('error', 'Bu şikayət artıq bağlanıb!');
        }

        $request->validate([
            'is_bitme_tarix' => 'required|date',
            'is_bitme_saat' => 'required|date_format:H:i',
            'gorulen_is' => 'required|string|min:5',
        ]);

        // Şikayəti bağla
        $complaint->update([
            'status' => 'həll olundu',
            'is_bitme_tarix' => $request->is_bitme_tarix,
            'is_bitme_saat' => $request->is_bitme_saat,
            'kim_is_gorub' => $request->gorulen_is,
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        // PDF yarat
        try {
            $employeesById = Employee::whereIn(
                'id',
                collect($complaint->detallar ?? [])->pluck('employee_id')->filter()->unique()
            )->get()->keyBy('id');

            $pdf = Pdf::loadView('complaints.akt', [
                'complaint' => $complaint,
                'company' => $complaint->company,
                'garage' => $complaint->garage,
                'employeesById' => $employeesById,
            ]);

            $pdfPath = storage_path("app/public/akt/akt-{$complaint->id}.pdf");
            \Illuminate\Support\Facades\Storage::makeDirectory('public/akt');
            $pdf->save($pdfPath);
        } catch (\Exception $e) {
            // PDF yaradılmasa belə davam et
            \Log::error('PDF yaradılmadı: ' . $e->getMessage());
        }

        return redirect()->route('complaints.index')->with('success', '✅ Şikayət bağlandı! Akt PDF olaraq yaradıldı.');
    }

    // =============== PDF YÜKLƏ ===============
    public function downloadPdf($id)
    {
        $complaint = Complaint::with('bus')->findOrFail($id);

        $storedPdfPath = storage_path("app/public/akt/akt-{$complaint->id}.pdf");
        if (is_file($storedPdfPath)) {
            return response()->file($storedPdfPath, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        $employeesById = Employee::whereIn(
            'id',
            collect($complaint->detallar ?? [])->pluck('employee_id')->filter()->unique()
        )->get()->keyBy('id');

        $pdf = Pdf::loadView('complaints.akt', compact('complaint', 'employeesById'))
            ->setPaper('a4');

        return $pdf->stream("is-karti-{$complaint->id}.pdf");
    }
}
