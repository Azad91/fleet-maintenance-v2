<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('app.supported_locales', ['en']));

        // Priorities:
        // 1. Explicit user request (?lang=ru)
        // 2. Session (user previously chose)
        // 3. User preference (future: users.locale column)
        // 4. Browser preference (Accept-Language header)
        // 5. App default (env APP_LOCALE)
        $locale = $request->query('lang')
            ?? Session::get('locale')
            ?? $request->getPreferredLanguage($supported)
            ?? config('app.locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        // Persist choice in session so next request remembers it
        if ($request->query('lang')) {
            Session::put('locale', $locale);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
