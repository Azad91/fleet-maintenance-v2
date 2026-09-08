<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Əgər sorğuda artıq Request-ID varsa (məsələn Nginx və ya Load Balancer-dən gəlirsə) onu götür, yoxdursa yeni UUID yarat
        $requestId = $request->header('X-Request-ID', Str::uuid()->toString());

        // 2. Bu ID-ni Laravel Context-ə əlavə et (Bütün loglarda avtomatik görünəcək)
        Context::add('request_id', $requestId);

        // 3. Sorğunun özünə də əlavə et ki, Controller-lərdə lazım olsa istifadə edilə bilsin
        $request->headers->set('X-Request-ID', $requestId);

        // 4. Sorğunu işlət
        $response = $next($request);

        // 5. Yekun cavabın (Response) header-inə də bu ID-ni əlavə et ki, frontend/mobil bu ID-ni görsün
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
