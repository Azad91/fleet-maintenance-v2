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
        $garageId = $request->session()->get('current_garage_id');
        $companyId = $request->session()->get('current_company_id');

        if (! $garageId) {
            return redirect()->route('garage.selection');
        }

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
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
            return redirect()->route('garage.selection')
                ->with('error', __('messages.flash.garage_access_denied'));
        }

        // Set context (company_id from garage)
        GarageContext::set((int) $membership->id, $membership->company_id);

        return $next($request);
    }
}