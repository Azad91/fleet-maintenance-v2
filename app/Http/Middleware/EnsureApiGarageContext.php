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
                'error' => __('messages.api.garage_header_required'),
            ], 400);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => __('messages.api.unauthenticated')], 401);
        }

        if ($user->isSuperAdmin()) {
            $garage = Garage::find($garageId);
            if (! $garage) {
                return response()->json([
                    'error' => __('messages.flash.garage_not_found'),
                ], 404);
            }
            GarageContext::set((int) $garageId, $garage->company_id);
            return $next($request);
        }

        $hasAccess = $user->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->exists();

        if (! $hasAccess) {
            return response()->json([
                'error' => __('messages.flash.garage_access_denied'),
            ], 403);
        }

        $garage = Garage::find($garageId);
        if (! $garage) {
            return response()->json(['error' => __('messages.flash.garage_not_found')], 404);
        }

        GarageContext::set((int) $garageId, $garage->company_id);

        return $next($request);
    }
}