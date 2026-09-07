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
        // 1. Header-dən qaraj ID-sini al
        $garageId = $request->header('X-Garage-Id');

        if (!$garageId) {
            return response()->json([
                'error' => 'X-Garage-Id header tələb olunur.'
            ], 400);
        }

        // 2. İstifadəçinin authenticated olduğundan əmin ol
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // 3. İstifadəçinin bu qaraja giriş icazəsi varmı?
        $hasAccess = $user->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'error' => 'Bu qaraja daxil olmaq üçün icazəniz yoxdur.'
            ], 403);
        }

        // 4. Qaraj məlumatlarını tap
        $garage = Garage::find($garageId);
        if (!$garage) {
            return response()->json(['error' => 'Qaraj tapılmadı'], 404);
        }

        // 5. Context-i təyin et
        GarageContext::set((int) $garageId, $garage->company_id);

        return $next($request);
    }
}
