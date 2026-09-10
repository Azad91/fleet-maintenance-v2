<?php

use App\Exceptions\GarageAccessDeniedException;
use App\Exceptions\StockInsufficientException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ Trust proxies YALNIZ env dəyişəni ilə konfiqurasiya olunur
        // Production-da: TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
        // Local-də: TRUSTED_PROXIES= (boş → heç bir proxy-yə inanma)
        $trustedProxies = env('TRUSTED_PROXIES');
        if ($trustedProxies !== null && $trustedProxies !== '') {
            $proxies = $trustedProxies === '*'
                ? '*'
                : array_map('trim', explode(',', $trustedProxies));
            $middleware->trustProxies(at: $proxies);
        }

        // Global middleware — hər request üçün
        $middleware->append(\App\Http\Middleware\RequestIdMiddleware::class);

        // Web middleware-ə SetLocale əlavə et — dil seçimi üçün
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'role'            => \App\Http\Middleware\RoleMiddleware::class,
            'garage.selected' => \App\Http\Middleware\EnsureGarageSelected::class,
            'idempotent'      => \App\Http\Middleware\IdempotencyMiddleware::class,
            'api.garage'      => \App\Http\Middleware\EnsureApiGarageContext::class,
        ]);

        // API route-larında qaraj kontekstini məcburi edirik
        $middleware->api(append: [
            \App\Http\Middleware\EnsureApiGarageContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ============================================================
        // 1. GARAGE ACCESS DENIED — 403
        // ============================================================
        $exceptions->render(function (GarageAccessDeniedException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }
            return redirect()->route('garage.selection')->with('error', $e->getMessage());
        });

        // ============================================================
        // 2. STOCK INSUFFICIENT — 422
        // ============================================================
        $exceptions->render(function (StockInsufficientException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage())->withInput();
        });

        // ============================================================
        // 3. VALIDATION — 422
        // ============================================================
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors'  => $e->errors(),
                ], 422);
            }
            // ✅ DÜZƏLİŞ: errorBag null ola bilər — explicit default
            return redirect()->back()
                ->withErrors($e->errors(), $e->errorBag ?? 'default')
                ->withInput();
        });

        // ============================================================
        // 4. MODEL NOT FOUND — 404
        // ============================================================
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Resource not found'], 404);
            }
            return abort(404);
        });

        // ============================================================
        // 5. NOT FOUND (HTTP-level 404) — 404
        // ============================================================
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Endpoint not found'], 404);
            }
            // Laravel default 404 səhifəsinə fallback
        });

        // ============================================================
        // 6. AUTHENTICATION — 401
        // ============================================================
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        });

        // ============================================================
        // 7. AUTHORIZATION — 403
        // ============================================================
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }
            return abort(403, $e->getMessage());
        });

        // ============================================================
        // 8. HTTP EXCEPTIONS (CSRF 419, Throttle 429, və s.) — statusa uyğun
        // ============================================================
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->expectsJson()) {
                // HTML üçün Laravel default davranışına fallback
                return null;
            }

            $status = $e->getStatusCode();
            $message = match ($status) {
                419 => 'Sessiyanın vaxtı bitdi. Zəhmət olmasa səhifəni yeniləyin.',
                429 => 'Çox sayda sorğu göndərildi. Bir az gözləyin.',
                default => $e->getMessage() ?: 'HTTP xətası',
            };

            return response()->json([
                'message' => $message,
                'status'  => $status,
            ], $status);
        });

        // ============================================================
        // 9. FALLBACK — 500 (yalnız JSON üçün)
        // ============================================================
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                // HTML üçün Laravel default xəta səhifəsi
                return null;
            }

            $isDebug = config('app.debug');

            return response()->json([
                'message' => 'Server error',
                'error'   => $isDebug ? $e->getMessage() : 'Internal server error',
                'trace'   => $isDebug ? collect($e->getTrace())->take(5)->toArray() : null,
            ], 500);
        });

        // ============================================================
        // REPORTS — Loglama və monitorinq
        // ============================================================
        $exceptions->reportable(function (Throwable $e) {
            // 404, 403, 419, 422 kimi gözlənilən xətaları loglamırıq
            if ($e instanceof ModelNotFoundException
                || $e instanceof NotFoundHttpException
                || $e instanceof AuthorizationException
                || $e instanceof AuthenticationException
                || $e instanceof ValidationException
                || $e instanceof HttpExceptionInterface) {
                return false; // false = Laravel default logger-inə də xəbər vermə
            }

            // Real server xətalarını loglayırıq
            \Log::error($e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
                'user_id'   => auth()->id(),
                'url'       => request()?->fullUrl(),
            ]);
        });
    })->create();
