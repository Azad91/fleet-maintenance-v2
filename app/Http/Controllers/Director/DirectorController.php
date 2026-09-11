<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Company;
use App\Models\Garage;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Read-only, company-scoped controller for Company Directors.
 *
 * Directors do NOT have garage context and cannot modify data.
 * All queries are explicitly filtered by the Director's company.
 */
class DirectorController extends Controller
{
    /**
     * Company-wide dashboard: aggregated KPIs + garage list.
     */
    public function dashboard(): View|RedirectResponse
    {
        $company = $this->resolveCompany();

        if (! $company) {
            return redirect()->route('login')
                ->with('error', __('messages.flash.director_no_company'));
        }

        $garageIds = $company->garages()->pluck('id');

        $stats = [
            'total_garages'         => $garageIds->count(),
            'active_garages'        => $company->garages()->where('is_active', true)->count(),
            'total_buses'           => $this->busQuery($garageIds)->count(),
            'active_buses'          => $this->busQuery($garageIds)->where('is_active', true)->count(),
            'open_complaints'       => $this->complaintQuery($garageIds)->where('status', '!=', 'completed')->count(),
            'total_warehouse_items' => $this->warehouseQuery($garageIds)->sum('quantity'),
        ];

        $garages = $company->garages()
            ->withCount([
                'buses',
                'complaints as open_complaints_count' => fn ($q) => $q->where('status', '!=', 'completed'),
            ])
            ->orderBy('name')
            ->get();

        return view('director.dashboard', compact('company', 'stats', 'garages'));
    }

    /**
     * Read-only list of all garages under the Director's company.
     */
    public function garages(): View|RedirectResponse
    {
        $company = $this->resolveCompany();

        if (! $company) {
            return redirect()->route('login')
                ->with('error', __('messages.flash.director_no_company'));
        }

        $garages = $company->garages()
            ->withCount(['buses', 'complaints', 'warehouses', 'employees', 'drivers'])
            ->orderBy('name')
            ->get();

        return view('director.garages.index', compact('company', 'garages'));
    }

    /**
     * Read-only summary of a specific garage.
     */
    public function showGarage(Garage $garage): View|RedirectResponse
    {
        $company = $this->resolveCompany();

        // Security: ensure the garage belongs to the Director's company.
        if (! $company || $garage->company_id !== $company->id) {
            abort(403);
        }

        $stats = [
            'total_buses'     => $this->busQuery([$garage->id])->count(),
            'active_buses'    => $this->busQuery([$garage->id])->where('is_active', true)->count(),
            'open_complaints' => $this->complaintQuery([$garage->id])->where('status', '!=', 'completed')->count(),
            'total_employees' => $garage->employees()->count(),
            'total_drivers'   => $garage->drivers()->count(),
        ];

        return view('director.garages.show', compact('company', 'garage', 'stats'));
    }

    // ==================== HELPERS ====================

    /**
     * Resolve the active Director company for the authenticated user.
     */
    private function resolveCompany(): ?Company
    {
        return auth()->user()?->activeDirectorCompany();
    }

    /**
     * Bus query builder — explicitly company-scoped, ignoring the garage global scope.
     *
     * @param  \Illuminate\Support\Collection|array<int>  $garageIds
     */
    private function busQuery($garageIds)
    {
        return Bus::withoutGlobalScope('garage')->whereIn('garage_id', $garageIds);
    }

    /**
     * Complaint query builder — explicitly company-scoped.
     *
     * @param  \Illuminate\Support\Collection|array<int>  $garageIds
     */
    private function complaintQuery($garageIds)
    {
        return Complaint::withoutGlobalScope('garage')->whereIn('garage_id', $garageIds);
    }

    /**
     * Warehouse query builder — explicitly company-scoped.
     *
     * @param  \Illuminate\Support\Collection|array<int>  $garageIds
     */
    private function warehouseQuery($garageIds)
    {
        return Warehouse::withoutGlobalScope('garage')->whereIn('garage_id', $garageIds);
    }
}
