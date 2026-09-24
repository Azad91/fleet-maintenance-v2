<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Garage;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarageSelectionController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        // Optional case-insensitive substring filter applied to both
        // company names and garage names. When empty, the full list is
        // shown — the previous behaviour, kept for backwards
        // compatibility and for the SuperAdmin "show everything" case.
        $search = trim((string) $request->input('q', ''));

        if ($user->isSuperAdmin()) {
            $companies = $this->superAdminCompanies($search);
        } else {
            $companies = $this->userCompanies($user, $search);
        }

        // ───────────────────────────────────────────────────────────
        // FIX P0-2: Infinite redirect loop
        //
        // ƏVVƏL: Heç bir qarajı olmayan istifadəçi /dashboard-a
        // redirect olunurdu. /dashboard isə `garage.selected`
        // middleware tərəfindən yenidən bura qaytarılırdı → sonsuz loop.
        //
        // İNDİ: Statik "giriş yoxdur" səhifəsi göstərilir. İstifadəçi
        // yalnız logout edə bilər. Bu, həm loop-u bitirir, həm də
        // istifadəçiyə aydın mesaj verir.
        //
        // QEYD: Search filteri aktiv olduqda boş nəticə "giriş yoxdur"
        // demək deyil — istifadəçi sadəcə fərqli söz axtarmalıdır.
        // ───────────────────────────────────────────────────────────
        $hasNoGarages = $companies->isEmpty()
            || $companies->every(fn ($c) => $c->garages->isEmpty());

        if ($hasNoGarages && ! $user->isSuperAdmin() && $search === '') {
            return view('garage-no-access');
        }

        return view('garage-selection', compact('companies', 'search'));
    }

    public function selectGarage(Request $request): RedirectResponse
    {
        $request->validate([
            'garage_id' => 'required|integer|exists:garages,id',
        ]);

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $garage = Garage::with('company')
                ->whereKey($request->garage_id)
                ->where('is_active', true)
                ->firstOrFail();
        } else {
            $garage = $user->garages()
                ->whereKey($request->garage_id)
                ->wherePivot('is_active', true)
                ->with('company')
                ->first();

            if (! $garage) {
                return redirect()->route('garage.selection')
                    ->with('error', __('messages.flash.garage_access_denied'));
            }
        }

        session([
            'current_garage_id' => $garage->id,
            'current_garage_name' => $garage->name,
            'current_company_id' => $garage->company_id,
            'current_company_name' => $garage->company?->name,
        ]);

        GarageContext::set($garage->id, $garage->company_id);

        $user->update([
            'current_garage_id' => $garage->id,
            'current_company_id' => $garage->company_id,
            'last_selected_garage_at' => now(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', __('messages.flash.garage_selected', ['name' => $garage->name]));
    }

    // ==================== HELPERS ====================

    /**
     * Companies visible to a SuperAdmin: every company that has at
     * least one active garage, with optional name filter.
     *
     * When $search is non-empty, both the company list and the nested
     * garage list are narrowed by an ILIKE substring match. This keeps
     * the rendered HTML size bounded on large platforms without
     * breaking the "select any garage" workflow — the user simply
     * types the target name to filter.
     */
    private function superAdminCompanies(string $search)
    {
        return Company::query()
            ->whereHas('garages', function ($query) use ($search) {
                $query->where('is_active', true);

                if ($search !== '') {
                    $query->where('name', 'ILIKE', "%{$search}%");
                }
            })
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                // Keep companies whose own name matches, even when
                // the matched garage list below is a subset.
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhereHas('garages', function ($gq) use ($search) {
                        $gq->where('is_active', true)
                            ->where('name', 'ILIKE', "%{$search}%");
                    });
            }))
            ->with(['garages' => function ($query) use ($search) {
                $query->where('is_active', true)
                    ->when($search !== '', fn ($q) => $q->where('name', 'ILIKE', "%{$search}%"))
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Companies visible to a regular user: only garages the user is
     * actively a member of. Optional name filter applied the same way
     * as for the SuperAdmin list.
     */
    private function userCompanies($user, string $search)
    {
        $garageAccessFilter = function ($query) use ($user, $search) {
            $query->where('is_active', true)
                ->whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->where('garage_user.is_active', true);
                });

            if ($search !== '') {
                $query->where('name', 'ILIKE', "%{$search}%");
            }
        };

        return Company::query()
            ->whereHas('garages', $garageAccessFilter)
            ->with(['garages' => function ($query) use ($garageAccessFilter) {
                $garageAccessFilter($query);
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
    }
}
