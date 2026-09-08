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

        // SUPER_ADMIN – qarajı yoxla, context-i təyin et
        if ($user->isSuperAdmin()) {
            $garage = Garage::find($garageId);
            if (! $garage) {
                $request->session()->forget(['current_garage_id', 'current_garage_name', 'current_company_id', 'current_company_name']);
                return redirect()->route('garage.selection')->with('error', 'Seçilmiş qaraj tapılmadı.');
            }
            GarageContext::set($garage->id, $garage->company_id);
            return $next($request);
        }

        // Normal user – membership yoxla
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

        // Context-i təyin et (company_id-ni garage-dan götür)
        GarageContext::set((int) $membership->id, $membership->company_id);

        return $next($request);
    }
}
