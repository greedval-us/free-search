<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveRequestLocale
{
    private const SUPPORTED_LOCALES = ['en', 'ru'];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $fallbackLocale = $this->supportedLocale((string) config('app.fallback_locale', 'en'));
        $locale = strtolower(trim((string) $request->cookie('locale', config('app.locale', $fallbackLocale))));

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = $fallbackLocale;
        }

        app()->setLocale($locale);

        return $next($request);
    }

    private function supportedLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'en';
    }
}
