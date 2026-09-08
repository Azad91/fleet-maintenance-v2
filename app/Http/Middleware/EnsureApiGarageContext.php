<?php

namespace App\Http\Middleware;

use App\Models\Garage;
use App\Services\GarageContext;
use Closure;
use Illuminate\Http\Request;

class EnsureApiGarageContext
{
    public function handle(Request $request, Closure $next)
    {
        $garageId = $request->header('X-Garage-Id');

        if (! $garageId) {
            return response()->json([
                'error' => 'X-Garage-Id header tələb olunur.',
            ], 400);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Super-admin üçün qarajın mövcudluğunu yoxla
        if ($user->isSuperAdmin()) {
            $garage = Garage::find($garageId);
            if (! $garage) {
                return response()->json([
                    'error' => 'Qaraj tapılmadı.',
                ], 404);
            }
            GarageContext::set((int) $garageId, $garage->company_id);
            return $next($request);
        }

        // Normal istifadəçi üçün membership yoxla
        $hasAccess = $user->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->exists();

        if (! $hasAccess) {
            return response()->json([
                'error' => 'Bu qaraja daxil olmaq üçün icazəniz yoxdur.',
            ], 403);
        }

        $garage = Garage::find($garageId);
        if (! $garage) {
            return response()->json(['error' => 'Qaraj tapılmadı'], 404);
        }

        GarageContext::set((int) $garageId, $garage->company_id);

        return $next($request);
    }
}
