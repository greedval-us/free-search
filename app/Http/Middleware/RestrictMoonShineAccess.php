<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictMoonShineAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $enforce = (bool) env('MOONSHINE_ENFORCE_IP_ALLOWLIST', false);
        if (!$enforce || !app()->environment('production')) {
            return $next($request);
        }

        $allowed = collect(explode(',', (string) env('MOONSHINE_ALLOWED_IPS', '')))
            ->map(static fn (string $ip): string => trim($ip))
            ->filter(static fn (string $ip): bool => $ip !== '')
            ->values()
            ->all();

        if ($allowed === []) {
            abort(403);
        }

        if (!in_array((string) $request->ip(), $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
