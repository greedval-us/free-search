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
        $enforce = (bool) config('moonshine.access.enforce_ip_allowlist', false);
        if (!$enforce || !app()->environment('production')) {
            return $next($request);
        }

        /** @var array<int, string> $allowed */
        $allowed = config('moonshine.access.allowed_ips', []);

        if ($allowed === []) {
            abort(403);
        }

        if (!in_array((string) $request->ip(), $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
