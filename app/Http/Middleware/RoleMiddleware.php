<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\RoleEnum;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $garageId = $request->hasSession()
            ? $request->session()->get('current_garage_id')
            : session('current_garage_id');
        $companyId = $request->hasSession()
            ? $request->session()->get('current_company_id')
            : session('current_company_id');

        if (!$garageId) {
            return redirect()->route('garage.selection');
        }

        $user = Auth::user();

        // Super Admin hər şeyə girə bilər
        if ($user->isSuperAdmin()) {
            \App\Services\GarageContext::set((int) $garageId, $companyId ? (int) $companyId : null);
            return $next($request);
        }

        // İstifadəçi bu qaraja aid deyilsə və ya passivdirsə
        $membership = $user->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->first();

        if (!$membership) {
            if ($request->hasSession()) {
                $request->session()->forget([
                    'current_garage_id',
                    'current_garage_name',
                    'current_company_id',
                    'current_company_name',
                ]);
            }
            \App\Services\GarageContext::clear();
            return redirect()->route('garage.selection')
                ->with('error', 'Seçilmiş qaraja daxil olmaq üçün icazəniz yoxdur.');
        }

        \App\Services\GarageContext::set((int) $garageId, $companyId ? (int) $companyId : null);

        // Heç bir rol tələb olunmursa, keçir
        if (empty($roles)) {
            return $next($request);
        }

        if ($user->hasGarageRole($roles, $garageId)) {
            return $next($request);
        }

        abort(403, 'Bu əməliyyat üçün icazəniz yoxdur.');
    }
}
