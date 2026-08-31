<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Yalnız qaraj seçilibsə davam et
        if (!session('current_garage_id')) {
            return redirect()->route('garage.selection');
        }

        return $next($request);
    }
}
