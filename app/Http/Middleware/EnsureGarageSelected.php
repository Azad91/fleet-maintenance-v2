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

        $garageId = $request->session()->get('current_garage_id');
        $companyId = $request->session()->get('current_company_id');

        // Directors have no garage context. If they somehow reach a
        // garage-scoped route without a selected garage, send them to
        // their company dashboard.
        if (! $garageId && $user->isDirector()) {
            return redirect()->route('director.dashboard');
        }

        // No garage in session — redirect with a clear message so the
        // user understands why they were sent to the selection screen.
        if (! $garageId) {
            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_garage'));
        }

        // Super admin — verify garage exists, set context
        if ($user->isSuperAdmin()) {
            $garage = Garage::find($garageId);

            if (! $garage) {
                $this->clearGarageSession($request);

                return redirect()
                    ->route('garage.selection')
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
            $this->clearGarageSession($request);
            GarageContext::clear();

            // If they are also a Director (no membership), send them to
            // the director dashboard instead of back to selection.
            if ($user->isDirector()) {
                return redirect()->route('director.dashboard');
            }

            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.garage_access_denied'));
        }

        GarageContext::set((int) $membership->id, $membership->company_id);

        return $next($request);
    }

    /**
     * Remove garage-related entries from the session.
     */
    private function clearGarageSession(Request $request): void
    {
        $request->session()->forget([
            'current_garage_id',
            'current_garage_name',
            'current_company_id',
            'current_company_name',
        ]);
    }
}
