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

        if (empty($roles)) {
            return $next($request);
        }

        if (Auth::user()->hasGarageRole($roles, session('current_garage_id'))) {
            return $next($request);
        }

        abort(403, 'Bu səhifəyə giriş icazəniz yoxdur.');
    }
}
