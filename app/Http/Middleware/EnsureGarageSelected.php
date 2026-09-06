<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\GarageContext;
use Illuminate\Http\Request;

class EnsureGarageSelected
{
    public function handle(Request $request, Closure $next)
    {
        $garageId = $request->hasSession() ? $request->session()->get('current_garage_id') : session('current_garage_id');
        $companyId = $request->hasSession() ? $request->session()->get('current_company_id') : session('current_company_id');

        $user = $request->user();

        if (!$garageId || !$user) {
            return redirect()->route('garage.selection');
        }

        // Super admin istənilən qaraja girə bilər, digər istifadəçilər isə qarajın aktiv üzvü olmalıdır
        $isSuperAdmin = method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        $hasGarageAccess = $isSuperAdmin || $user->garages()->whereKey($garageId)->wherePivot('is_active', true)->exists();

        if (!$hasGarageAccess) {
            if ($request->hasSession()) {
                $request->session()->forget([
                    'current_garage_id',
                    'current_garage_name',
                    'current_company_id',
                    'current_company_name',
                ]);
            }
            GarageContext::clear();

            return redirect()->route('garage.selection')->with('error', 'Seçilmiş qaraja daxil olmaq üçün icazəniz yoxdur.');
        }

        // Qaraj ID-ni Context-ə yaz
        GarageContext::set((int) $garageId, $companyId ? (int) $companyId : null);

        return $next($request);
    }
}

