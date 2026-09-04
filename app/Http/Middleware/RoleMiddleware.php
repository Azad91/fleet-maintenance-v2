<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!session('current_garage_id')) {
            return redirect()->route('garage.selection');
        }

        $user = Auth::user();

        // Super Admin hər şeyə girə bilər
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Heç bir rol tələb olunmursa, keçir
        if (empty($roles)) {
            return $next($request);
        }

        // İstifadəçinin cari qarajda bu rollardan biri varmı?
        if ($user->hasGarageRole($roles, session('current_garage_id'))) {
            return $next($request);
        }

        abort(403, 'Bu əməliyyat üçün icazəniz yoxdur.');
    }
}
