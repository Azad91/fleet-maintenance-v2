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
use Illuminate\Validation\ValidationException;

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

        return view('complaints.create', compact(
            'buses',
            'complaintTypes',
            'serviceTemplates',
            'employees'
        ));
    }

    public function store(ComplaintStoreRequest $request)
    {
        $data = $request->validated();

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

        DB::transaction(function () use ($request, &$data) {
            // Detalları JSON olaraq saxla (anbar əməliyyatları ilə birlikdə)
            if ($request->has('detallar') && is_array($request->detallar)) {
                $detallar = [];
                foreach ($request->detallar as $detal) {
                    if (!empty($detal['kodu'])) {
                        $warehouse = Warehouse::where('kod', $detal['kodu'])->lockForUpdate()->first();

                        // 🔥 MƏNFİ STOK YOXLANIŞI (ƏLAVƏ EDİLDİ)
                        $usedQuantity = (int) ($detal['islenen_miqdar'] ?? 0);
                        if ($warehouse && $warehouse->miqdar < $usedQuantity) {
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
                            'qeyd' => $detal['qeyd'] ?? null,
                        ];

                        if ($warehouse && !empty($detal['islenen_miqdar']) && $detal['islenen_miqdar'] > 0) {
                            $warehouse->miqdar = $warehouse->miqdar - $detal['islenen_miqdar'];
                            $warehouse->save();
                        }
                    }
                }
                // 🔥 JSON MƏNTİQİ TƏMİZLƏNDİ (model casts 'array' olduğu üçün)
                $data['detallar'] = $detallar; // əvvəl: json_encode(...)
            } else {
                $data['detallar'] = null;
            }

            Complaint::create($data);
        });

        return redirect()->route('complaints.index')->with('success', 'Şikayət uğurla əlavə edildi!');
    }

    public function show($id)
    {
        $complaint = Complaint::with('bus')->findOrFail($id);

        if ($complaint->detallar) {
            $complaint->detallar = is_array($complaint->detallar) ? $complaint->detallar : json_decode($complaint->detallar, true);
        }

        return view('complaints.show', compact('complaint'));
    }

    public function edit($id)
    {
        $complaint = Complaint::findOrFail($id);
        $buses = Bus::orderBy('xett_no')->get();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $employees = Employee::active()->orderBy('ad')->get();

        $detallar = [];
        if ($complaint->detallar) {
            $detallar = is_array($complaint->detallar) ? $complaint->detallar : json_decode($complaint->detallar, true);
        }

        return view('complaints.edit', compact(
            'complaint',
            'buses',
            'complaintTypes',
            'detallar',
            'employees'
        ));
    }

    public function update(ComplaintUpdateRequest $request, $id)
    {
        $complaint = Complaint::findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $complaint) {
            // Köhnə detalları al
            $oldDetallar = $complaint->detallar ?? [];

            if (is_string($oldDetallar)) {
                $oldDetallar = json_decode($oldDetallar, true) ?? [];
            }
            if (!is_array($oldDetallar)) {
                $oldDetallar = [];
            }

            // BÜTÜN SAHƏLƏR (validated data-dan)
            $complaint->status = $validated['status'];
            $complaint->yer = $validated['yer'];
            $complaint->surucu_adi = $validated['surucu_adi'] ?? null;
            $complaint->km = $validated['km'] ?? null;
            $complaint->sikayet_tipi = $validated['sikayet_tipi'] ?? null;
            $complaint->kim_is_gorub = $validated['kim_is_gorub'] ?? null;
            $complaint->bildirilme_tarix = $validated['bildirilme_tarix'] ?? null;
            $complaint->bildirilme_saat = $validated['bildirilme_saat'] ?? null;
            $complaint->is_baslama_tarix = $validated['is_baslama_tarix'] ?? null;
            $complaint->is_baslama_saat = $validated['is_baslama_saat'] ?? null;
            $complaint->is_bitme_tarix = $validated['is_bitme_tarix'] ?? null;
            $complaint->is_bitme_saat = $validated['is_bitme_saat'] ?? null;

            if ($request->has('employee_id')) {
                $complaint->employee_id = $request->employee_id;
            }

            if (!empty($validated['shikayet']) && is_array($validated['shikayet'])) {
                $complaint->shikayet = implode("\n", array_filter($validated['shikayet']));
            }

            // Köhnə detalları anbara geri qaytar
            foreach ($oldDetallar as $old) {
                if (!empty($old['kodu']) && !empty($old['islenen_miqdar'])) {
                    $warehouse = Warehouse::where('kod', $old['kodu'])->lockForUpdate()->first();
                    if ($warehouse) {
                        $warehouse->miqdar = $warehouse->miqdar + $old['islenen_miqdar'];
                        $warehouse->save();
                    }
                }
            }

            // Yeni detalları saxla (mənfi yoxlanışı ilə)
            if (!empty($validated['detallar']) && is_array($validated['detallar'])) {
                $detallar = [];
                foreach ($validated['detallar'] as $detal) {
                    if (!empty($detal['kodu'])) {
                        $warehouse = Warehouse::where('kod', $detal['kodu'])->lockForUpdate()->first();

                        // 🔥 MƏNFİ STOK YOXLANIŞI (ƏLAVƏ EDİLDİ)
                        $usedQuantity = (int) ($detal['islenen_miqdar'] ?? 0);
                        if ($warehouse && $warehouse->miqdar < $usedQuantity) {
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
                            'qeyd' => $detal['qeyd'] ?? null,
                        ];

                        if ($warehouse && !empty($detal['islenen_miqdar']) && $detal['islenen_miqdar'] > 0) {
                            $warehouse->miqdar = $warehouse->miqdar - $detal['islenen_miqdar'];
                            $warehouse->save();
                        }
                    }
                }
                // 🔥 JSON MƏNTİQİ TƏMİZLƏNDİ
                $complaint->detallar = $detallar; // əvvəl: json_encode(...)
            } else {
                $complaint->detallar = null;
            }

            if ($request->has('service_template_id')) {
                $complaint->service_template_id = $request->service_template_id;
            }
            if ($request->has('service_km')) {
                $complaint->service_km = $request->service_km;
            }

            $complaint->save();
        });

        return redirect('/complaints')->with('success', 'Şikayət uğurla yeniləndi!');
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
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new ComplaintsImport, $request->file('file'));
            return redirect()->route('complaints.index')->with('success', 'Şikayətlər uğurla idxal edildi!');
        } catch (\Exception $e) {
            return redirect()->route('complaints.index')->with('error', 'Xəta baş verdi: ' . $e->getMessage());
        }
    }
}
