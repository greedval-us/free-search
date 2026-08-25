<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleSensitivePublicEndpoints
{
    public function __construct(
        private readonly ThrottleRequests $throttleRequests,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $limiter = match (true) {
            $request->isMethod('POST') && $request->is('register') => 'registration',
            $request->isMethod('POST') && $request->is('forgot-password') => 'password-reset',
            default => null,
        };

        if ($limiter === null) {
            return $next($request);
        }

        return $this->throttleRequests->handle($request, $next, $limiter);
    }
}
