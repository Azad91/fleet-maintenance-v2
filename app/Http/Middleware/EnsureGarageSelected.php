<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\GarageContext;

class EnsureGarageSelected
{
    public function handle($request, Closure $next)
    {
        $garageId = session('current_garage_id');
        $companyId = session('current_company_id');

        if (!$garageId) {
            return redirect('/select-garage');
        }

        // ✅ Qaraj ID-ni Context-ə yaz
        GarageContext::set($garageId, $companyId);

        return $next($request);
    }
}
