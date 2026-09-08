<?php

namespace App\Http\Middleware;

use App\Services\GarageContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // ✅ SUPER_ADMIN hər şeyə girə bilər
        if ($user->isSuperAdmin()) {
            // Qaraj kontekstini təyin et (əgər varsa)
            $garageId = $request->session()->get('current_garage_id');
            $companyId = $request->session()->get('current_company_id');
            if ($garageId) {
                GarageContext::set((int) $garageId, $companyId ? (int) $companyId : null);
            }
            return $next($request);
        }

        // Qaraj seçilib?
        $garageId = $request->session()->get('current_garage_id');
        if (! $garageId) {
            return redirect()->route('garage.selection');
        }

        // İstifadəçi bu qaraja aid deyilsə...
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
                ->with('error', 'Seçilmiş qaraja daxil olmaq üçün icazəniz yoxdur.');
        }

        GarageContext::set((int) $garageId, $request->session()->get('current_company_id') ? (int) $request->session()->get('current_company_id') : null);

        // Əgər rol tələb olunmursa, keç
        if (empty($roles)) {
            return $next($request);
        }

        if ($user->hasGarageRole($roles, $garageId)) {
            return $next($request);
        }

        abort(403, 'Bu əməliyyat üçün icazəniz yoxdur.');
    }
}
