<?php

use App\Exceptions\GarageAccessDeniedException;
use App\Exceptions\StockInsufficientException;
use App\Http\Middleware\EnsureGarageSelected;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'garage.selected' => EnsureGarageSelected::class,
            'idempotent' => IdempotencyMiddleware::class,
            'api.garage' => \App\Http\Middleware\EnsureApiGarageContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // GarageAccessDeniedException
        $exceptions->render(function (GarageAccessDeniedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }
            return redirect()->route('garage.selection')->with('error', $e->getMessage());
        });

        // StockInsufficientException
        $exceptions->render(function (StockInsufficientException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage())->withInput();
        });

        // ValidationException
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors(), $e->errorBag)->withInput();
        });

        // ModelNotFoundException
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Resource not found'], 404);
            }
            return abort(404);
        });

        // AuthenticationException
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        });

        // AuthorizationException
        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }
            return abort(403, $e->getMessage());
        });

        // Ümumi exception (yalnız JSON üçün)
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Server error',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ], 500);
            }
        });

        $exceptions->reportable(function (Throwable $e) {
            // Log::error($e->getMessage(), ['exception' => $e]);
        });
    })->create();
