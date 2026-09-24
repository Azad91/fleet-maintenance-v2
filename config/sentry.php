<?php

/**
 * Sentry configuration for the Fleet Maintenance application.
 *
 * The full set of options is provided by the sentry/sentry-laravel
 * package; this file only declares the values we want to override
 * or that operators are likely to tune per environment.
 *
 * Behaviour when SENTRY_LARAVEL_DSN is empty (the default):
 *   - The Sentry SDK initialises with a null client.
 *   - No events are sent anywhere, no network calls are made.
 *   - There is effectively zero runtime overhead.
 *
 * This makes the package safe to ship in every environment: local
 * development, CI, and a production deployment that has not yet
 * configured Sentry. Operators enable it by setting the DSN in
 * .env.production.
 */
return [

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'release' => env('SENTRY_RELEASE'),

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    'send_default_pii' => (bool) env('SENTRY_SEND_DEFAULT_PII', false),

    'attach_stacktrace' => (bool) env('SENTRY_ATTACH_STACKTRACE', false),

    'max_breadcrumbs' => (int) env('SENTRY_MAX_BREADCRUMBS', 50),

    /*
    |--------------------------------------------------------------------------
    | before_send — filter noisy / expected events
    |--------------------------------------------------------------------------
    |
    | The application deliberately renders a small set of exceptions as
    | normal HTTP responses:
    |
    |   - GarageAccessDeniedException  → 403 + redirect
    |   - StockInsufficientException   → 422 + redirect
    |   - MissingGarageContextException → 500 + redirect
    |
    | These are handled in bootstrap/app.php and are not bugs. Forwarding
    | them to Sentry would drown the real signal — a validation error
    | from a user is not an incident.
    |
    | Laravel's reportable() closure in bootstrap/app.php already skips
    | these, but adding the filter here too is cheap insurance against a
    | future refactor that accidentally reverts the reportable change.
    |
    */
    'before_send' => function (Sentry\Event $event): ?Sentry\Event {
        $exceptionsToIgnore = [
            App\Exceptions\GarageAccessDeniedException::class,
            App\Exceptions\StockInsufficientException::class,
            App\Exceptions\MissingGarageContextException::class,
            Illuminate\Validation\ValidationException::class,
            Illuminate\Auth\AuthenticationException::class,
            Illuminate\Auth\Access\AuthorizationException::class,
            Illuminate\Database\Eloquent\ModelNotFoundException::class,
            Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            Symfony\Component\HttpKernel\Exception\HttpExceptionInterface::class,
        ];

        foreach ($event->getExceptions() as $exception) {
            foreach ($exceptionsToIgnore as $ignored) {
                if ($exception instanceof $ignored) {
                    return null; // drop the event
                }
            }
        }

        return $event;
    },

];
