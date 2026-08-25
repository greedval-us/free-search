<?php

namespace App\Http\Middleware;

use App\Support\Security\ContentSecurityPolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class AddSecurityHeaders
{
    public function __construct(
        private readonly ContentSecurityPolicy $contentSecurityPolicy,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.headers.enabled', false)) {
            return $next($request);
        }

        $nonce = null;

        if ($this->usesContentSecurityPolicy($request)) {
            $nonce = Vite::useCspNonce();
        }

        $response = $next($request);

        foreach (config('security.headers.values', []) as $name => $value) {
            $response->headers->set($name, (string) $value);
        }

        if ($request->isSecure() && config('security.hsts.enabled', true)) {
            $response->headers->set(
                'Strict-Transport-Security',
                (string) config('security.hsts.value')
            );
        }

        if ($nonce !== null) {
            $response->headers->set(
                'Content-Security-Policy',
                $this->contentSecurityPolicy->build($nonce)
            );
        }

        return $response;
    }

    private function usesContentSecurityPolicy(Request $request): bool
    {
        if (! config('security.content_security_policy.enabled', true)) {
            return false;
        }

        foreach (config('security.content_security_policy.excluded_paths', []) as $path) {
            if ($request->is((string) $path)) {
                return false;
            }
        }

        return true;
    }
}
