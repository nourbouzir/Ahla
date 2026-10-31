<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language: the session choice first, then the signed-in
 * user's saved preference, then the browser's Accept-Language, then the default.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'fr', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale')
            ?? $request->user()?->preferred_language
            ?? $request->getPreferredLanguage(self::SUPPORTED)
            ?? config('app.locale');

        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : config('app.fallback_locale'));

        return $next($request);
    }
}
