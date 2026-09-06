<?php

use App\Http\Middleware\EnsureGarageSelected;
use App\Http\Middleware\RoleMiddleware;
use App\Exceptions\GarageAccessDeniedException;
use App\Exceptions\StockInsufficientException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'garage.selected' => EnsureGarageSelected::class,
            'idempotent' => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ✅ GarageAccessDeniedException - 403
        $exceptions->render(function (GarageAccessDeniedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }
            return redirect()->route('garage.selection')->with('error', $e->getMessage());
        });

        // ✅ StockInsufficientException - 422
        $exceptions->render(function (StockInsufficientException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage())->withInput();
        });

        // ✅ ValidationException - form xətaları (default)
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ], 422);
            }
            // ✅ DÜZƏLİŞ: $e->errorBag parametri əlavə edildi ki, named bag-lər itməsin
            return redirect()->back()->withErrors($e->errors(), $e->errorBag)->withInput();
        });

        // ✅ ModelNotFoundException - 404
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Resource not found'], 404);
            }
            return abort(404);
        });

        // ✅ AuthenticationException - 401
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        });

        // ✅ AuthorizationException - 403
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }
            return abort(403, $e->getMessage());
        });

        // ✅ Ümumi exception - 500 (yalnız JSON üçün)
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Server error',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ], 500);
            }
            // Web üçün Laravel default handler işləyəcək
        });

        // ✅ Exception reporting (Sentry və s. üçün)
        $exceptions->reportable(function (\Throwable $e) {
            // Log::error($e->getMessage(), ['exception' => $e]);
        });
    })->create();
