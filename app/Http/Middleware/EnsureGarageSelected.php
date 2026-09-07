<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\GarageContext;
use Illuminate\Http\Request;

class EnsureGarageSelected
{
    public function handle(Request $request, Closure $next)
    {
        $garageId = $request->hasSession()
            ? $request->session()->get('current_garage_id')
            : session('current_garage_id');

        $companyId = $request->hasSession()
            ? $request->session()->get('current_company_id')
            : session('current_company_id');

        // Qaraj seçilməyibsə, seçim səhifəsinə yönləndir
        if (!$garageId) {
            return redirect()->route('garage.selection');
        }

        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // ✅ Dəyişiklik: yalnız super_admin istənilən qaraja girə bilər
        if ($user->isSuperAdmin()) {
            GarageContext::set((int) $garageId, $companyId ? (int) $companyId : null);
            return $next($request);
        }

        // İstifadəçinin bu qaraja üzvlüyünü yoxla
        $membership = $user->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->first();

        if (!$membership) {
            // Session-ı təmizlə
            if ($request->hasSession()) {
                $request->session()->forget([
                    'current_garage_id',
                    'current_garage_name',
                    'current_company_id',
                    'current_company_name',
                ]);
            }
            GarageContext::clear();

            return redirect()->route('garage.selection')
                ->with('error', 'Seçilmiş qaraja daxil olmaq üçün icazəniz yoxdur.');
        }

        GarageContext::set((int) $garageId, $companyId ? (int) $companyId : null);
        return $next($request);
    }
}
