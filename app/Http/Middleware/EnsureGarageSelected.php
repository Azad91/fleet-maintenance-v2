<?php

namespace App\Http\Middleware;

use App\Models\Garage;
use App\Services\GarageContext;
use Closure;
use Illuminate\Http\Request;

class EnsureGarageSelected
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $garageId  = $request->session()->get('current_garage_id');
        $companyId = $request->session()->get('current_company_id');

        // Directors have no garage context. If they somehow reach a garage-scoped
        // route without a selected garage, send them to their company dashboard.
        if (! $garageId && $user->isDirector()) {
            return redirect()->route('director.dashboard');
        }

        if (! $garageId) {
            return redirect()->route('garage.selection');
        }

        // Super admin — verify garage exists, set context
        if ($user->isSuperAdmin()) {
            $garage = Garage::find($garageId);
            if (! $garage) {
                $request->session()->forget([
                    'current_garage_id', 'current_garage_name',
                    'current_company_id', 'current_company_name',
                ]);
                return redirect()->route('garage.selection')
                    ->with('error', __('messages.flash.garage_not_found'));
            }
            GarageContext::set($garage->id, $garage->company_id);
            return $next($request);
        }

        // Regular user — verify membership
        $membership = $user->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->first();

        if (! $membership) {
            $request->session()->forget([
                'current_garage_id',
                'current_garage_name',
                'current_company_id',
                'current_company_name',
            ]);
            GarageContext::clear();

            // If they are a Director (also no membership), send to company dashboard.
            if ($user->isDirector()) {
                return redirect()->route('director.dashboard');
            }

            return redirect()->route('garage.selection')
                ->with('error', __('messages.flash.garage_access_denied'));
        }

        GarageContext::set((int) $membership->id, $membership->company_id);

        return $next($request);
    }
}
