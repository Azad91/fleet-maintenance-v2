<?php

use App\Exceptions\GarageAccessDeniedException;
use App\Exceptions\MissingGarageContextException;
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
        // Trust proxies YALNIZ env dəyişəni ilə konfiqurasiya olunur
        $trustedProxies = env('TRUSTED_PROXIES');

        if ($trustedProxies !== null && $trustedProxies !== '') {
            $proxies = $trustedProxies === '*'
                ? '*'
                : array_map('trim', explode(',', $trustedProxies));

            $middleware->trustProxies(at: $proxies);
        }

        // Global middleware — hər request üçün
        $middleware->append(App\Http\Middleware\RequestIdMiddleware::class);

        // Web middleware additions
        // QEYD: EnforcePinChange web qrupundan çıxarıldı və
        // route-level `pin.enforced` alias kimi təyin olundu.
        // Səbəb: `auth`-dan əvvəl işləyirdi və kövrək idi.
        $middleware->web(append: [
            App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'role' => App\Http\Middleware\RoleMiddleware::class,
            'garage.selected' => App\Http\Middleware\EnsureGarageSelected::class,
            'idempotent' => App\Http\Middleware\IdempotencyMiddleware::class,
            'api.garage' => App\Http\Middleware\EnsureApiGarageContext::class,
            'super.admin' => App\Http\Middleware\EnsureSuperAdmin::class,
            'pin.enforced' => App\Http\Middleware\EnforcePinChange::class,
            '2fa.verified' => App\Http\Middleware\EnsureTwoFactorVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ============================================================
        // CUSTOM EXCEPTION RENDERERS
        // ============================================================

        // 1. GARAGE ACCESS DENIED — 403
        $exceptions->render(function (GarageAccessDeniedException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }

            return redirect()
                ->route('garage.selection')
                ->with('error', $e->getMessage());
        });

        // 2. STOCK INSUFFICIENT — 422
        $exceptions->render(function (StockInsufficientException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        });

        // 3. MISSING GARAGE CONTEXT — block silent data corruption
        $exceptions->render(function (MissingGarageContextException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.flash.no_garage'),
                    'error' => 'garage_context_missing',
                ], 500);
            }

            return redirect()
                ->route('garage.selection')
                ->with('error', __('messages.flash.no_garage'));
        });

        // 4. HTTP EXCEPTIONS (CSRF 419, Throttle 429) — localized JSON
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->expectsJson()) {
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
                'status' => $status,
            ], $status);
        });

        // 5. REPORTING
        $exceptions->reportable(function (Throwable $e) {
            if ($e instanceof ModelNotFoundException
                || $e instanceof NotFoundHttpException
                || $e instanceof AuthorizationException
                || $e instanceof AuthenticationException
                || $e instanceof ValidationException
                || $e instanceof HttpExceptionInterface) {
                return false;
            }

            Log::error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_id' => Illuminate\Support\Facades\Context::get('request_id'),
                'user_id' => auth()->id(),
                'url' => request()?->fullUrl(),
            ]);
        });
    })->create();
