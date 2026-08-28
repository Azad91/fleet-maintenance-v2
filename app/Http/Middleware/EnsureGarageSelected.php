<?php

namespace App\Http\Middleware;

use Closure;

class EnsureGarageSelected
{
    public function handle($request, Closure $next)
    {
        if (!session('current_garage_id')) {
            // 🔥 DƏYİŞİKLİK: route() əvəzinə url()
            return redirect('/select-garage');
        }
        return $next($request);
    }
}
