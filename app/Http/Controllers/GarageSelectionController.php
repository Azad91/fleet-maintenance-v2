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
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $companies = Company::query()
                ->whereHas('garages', fn ($q) => $q->where('is_active', true))
                ->with(['garages' => function ($query) {
                    $query->where('is_active', true)->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
        } else {
            $companies = Company::query()
                ->whereHas('garages', function ($query) use ($user) {
                    $query->where('is_active', true)
                        ->whereHas('users', function ($q) use ($user) {
                            $q->where('user_id', $user->id)
                                ->where('garage_user.is_active', true);
                        });
                })
                ->with(['garages' => function ($query) use ($user) {
                    $query->where('is_active', true)
                        ->whereHas('users', function ($q) use ($user) {
                            $q->where('user_id', $user->id)
                                ->where('garage_user.is_active', true);
                        })
                        ->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
        }

        if ($companies->isEmpty() || $companies->every(fn ($c) => $c->garages->isEmpty())) {
            if (! $user->isSuperAdmin()) {
                return redirect()->route('dashboard')
                    ->with('error', __('messages.flash.no_garage_assigned'));
            }
        }

        return view('garage-selection', compact('companies'));
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
            'current_garage_id'    => $garage->id,
            'current_garage_name'  => $garage->name,
            'current_company_id'   => $garage->company_id,
            'current_company_name' => $garage->company?->name,
        ]);

        GarageContext::set($garage->id, $garage->company_id);

        $user->update([
            'current_garage_id'       => $garage->id,
            'current_company_id'      => $garage->company_id,
            'last_selected_garage_at' => now(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', __('messages.flash.garage_selected', ['name' => $garage->name]));
    }
}
