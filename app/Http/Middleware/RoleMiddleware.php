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

        // Admin hər şeyi görə bilər
        if ($user->role === 'admin') {
            return $next($request);
        }

        if (!$currentGarageId) {
            return redirect()->route('garage.selection');
        }

        // 🔥 QARAJ ÜZRƏ ROL YOXLA
        $membership = $user
            ->garages()
            ->whereKey($currentGarageId)
            ->wherePivot('is_active', true)
            ->first();

        if (!$membership) {
            abort(403, 'Bu qaraja giriş icazəniz yoxdur.');
        }

        if (!in_array($membership->pivot->role, $roles)) {
            abort(403, 'Bu əməliyyat üçün icazəniz yoxdur.');
        }

        return $next($request);
    }
}
