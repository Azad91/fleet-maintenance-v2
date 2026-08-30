<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $currentGarageId = session('current_garage_id');

        if ($user->hasGarageRole('admin')) {
            return $next($request);
        }

        if (!$currentGarageId) {
            return redirect()->route('garage.selection');
        }

        // Qaraj üzrə rol yoxla
        if (! $user->garages()->whereKey($currentGarageId)->wherePivot('is_active', true)->exists()) {
            abort(403, 'Bu qaraja giriş icazəniz yoxdur.');
        }

        if (! $user->hasGarageRole($roles, $currentGarageId)) {
            abort(403, "Bu əməliyyat üçün icazəniz yoxdur. Tələb olunan rollar: " . implode(', ', $roles));
        }

        return $next($request);
    }
}
