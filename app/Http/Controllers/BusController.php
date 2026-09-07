<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusStoreRequest;
use App\Http\Requests\BusUpdateRequest;
use App\Imports\BusesImport;
use App\Models\Bus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BusController extends Controller
{
    use AuthorizesRequests;

    /**
     * Avtobuslar siyahısı
     */
    public function index()
    {
        $this->authorize('viewAny', Bus::class);

        $buses = Bus::with('latestKmRecord')
            ->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        return view('buses.index', compact('buses'));
    }

    /**
     * Avtobus axtarışı (AJAX)
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Bus::class);

        $bus_project = $request->bus_project;
        $vin = $request->vin;
        $uzunluq = $request->uzunluq;
        $route_number = $request->route_number;
        $dqn = $request->dqn;
        $engine_number = $request->engine_number;

        $query = Bus::with('latestKmRecord');

        if (! empty($bus_project)) {
            $query->where('bus_project', 'ILIKE', "%{$bus_project}%");
        }
        if (! empty($vin)) {
            $query->where('vin', 'ILIKE', "%{$vin}%");
        }
        if (! empty($uzunluq)) {
            $query->where('uzunluq', 'ILIKE', "%{$uzunluq}%");
        }
        if (! empty($route_number)) {
            $query->where('route_number', 'ILIKE', "%{$route_number}%");
        }
        if (! empty($dqn)) {
            $query->where('dqn', 'ILIKE', "%{$dqn}%");
        }
        if (! empty($engine_number)) {
            $query->where('engine_number', 'ILIKE', "%{$engine_number}%");
        }

        $buses = $query->orderBy('id', 'desc')
            ->paginate(config('settings.pagination', 15));

        $isEmpty = $buses->isEmpty();

        if ($request->ajax()) {
            return view('buses.partials.table', compact('buses', 'isEmpty'))->render();
        }

        return view('buses.index', compact('buses'));
    }

    /**
     * Avtobus məlumatları
     */
    public function show($id)
    {
        $bus = Bus::findOrFail($id);

        $this->authorize('view', $bus);

        return view('buses.show', compact('bus'));
    }

    /**
     * Yeni avtobus yaratmaq üçün forma
     */
    public function create()
    {
        $this->authorize('create', Bus::class);

        return view('buses.create');
    }

    /**
     * Yeni avtobus yarat
     */
    public function store(BusStoreRequest $request)
    {
        $this->authorize('create', Bus::class);

        $data = $request->validated();
        $data['date'] = now()->format('Y-m-d');
        $data = $this->addGarageContext($data);

        Bus::create($data);

        return redirect()->route('buses.index')
            ->with('success', 'Avtobus uğurla əlavə edildi!');
    }

    /**
     * Avtobus redaktə etmək üçün forma
     */
    public function edit($id)
    {
        $bus = Bus::findOrFail($id);

        $this->authorize('update', $bus);

        return view('buses.edit', compact('bus'));
    }

    /**
     * Avtobus məlumatlarını yenilə
     */
    public function update(BusUpdateRequest $request, $id)
    {
        $bus = Bus::findOrFail($id);

        $this->authorize('update', $bus);

        $data = $request->validated();
        $bus->update($data);

        return redirect()->route('buses.index')
            ->with('success', 'Avtobus uğurla yeniləndi!');
    }

    /**
     * Avtobus sil (soft delete)
     */
    public function destroy($id)
    {
        $bus = Bus::findOrFail($id);

        $this->authorize('delete', $bus);

        $bus->delete();

        return redirect()->route('buses.index')
            ->with('success', 'Avtobus uğurla silindi!');
    }

    /**
     * Excel import forması
     */
    public function importForm()
    {
        $this->authorize('import', Bus::class);

        return view('buses.import');
    }

    /**
     * Excel import et
     */
    public function import(Request $request)
    {
        $this->authorize('import', Bus::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(
                new BusesImport(
                    (int) session('current_garage_id'),
                    session('current_company_id') ? (int) session('current_company_id') : null
                ),
                $request->file('file')
            );

            return redirect()->route('buses.index')
                ->with('success', 'Avtobuslar uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('buses.index')
                ->with('error', 'İdxal zamanı xəta baş verdi. Faylın formatını yoxlayın və yenidən cəhd edin.');
        }
    }

    // ==================== BULK OPERATIONS ====================

    /**
     * Bulk deactivate - seçilmiş avtobusları passiv et
     */
    public function bulkDeactivate(Request $request)
    {
        $this->authorize('update', Bus::class);

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Heç bir avtobus seçilməyib.');
        }

        // Bulk update
        Bus::whereIn('id', $ids)->update(['is_active' => false]);

        // ✅ Bulk audit
        Bus::auditBulkUpdate($ids, ['is_active' => false], 'bulk_deactivated');

        return redirect()->route('buses.index')
            ->with('success', count($ids).' avtobus passiv edildi.');
    }

    /**
     * Bulk activate - seçilmiş avtobusları aktiv et
     */
    public function bulkActivate(Request $request)
    {
        $this->authorize('update', Bus::class);

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Heç bir avtobus seçilməyib.');
        }

        // Bulk update
        Bus::whereIn('id', $ids)->update(['is_active' => true]);

        // ✅ Bulk audit
        Bus::auditBulkUpdate($ids, ['is_active' => true], 'bulk_activated');

        return redirect()->route('buses.index')
            ->with('success', count($ids).' avtobus aktiv edildi.');
    }

    /**
     * Bulk delete - seçilmiş avtobusları sil
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('delete', Bus::class);

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Heç bir avtobus seçilməyib.');
        }

        // ✅ Bulk audit (silinmədən əvvəl)
        Bus::auditBulkDelete($ids);

        // Bulk delete
        Bus::whereIn('id', $ids)->delete();

        return redirect()->route('buses.index')
            ->with('success', count($ids).' avtobus silindi.');
    }
}
