<?php

namespace App\Http\Middleware;

use App\Support\Observability\MoonShineSecurityConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ThrottleMoonShineLoginAttempts
{
    public function __construct(
        private readonly MoonShineSecurityConfig $securityConfig,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isMoonShineLoginRequest($request)) {
            return $next($request);
        }

        $maxAttempts = $this->securityConfig->loginMaxAttempts();
        $decaySeconds = $this->securityConfig->loginDecaySeconds();
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => [__('auth.throttle', ['seconds' => $seconds])],
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }

    private function isMoonShineLoginRequest(Request $request): bool
    {
        $prefix = trim((string) config('moonshine.prefix', 'admin'), '/');
        $loginPath = $prefix !== '' ? $prefix.'/login' : 'login';

        return $request->isMethod('post') && $request->is($loginPath);
    }

    private function throttleKey(Request $request): string
    {
        $usernameField = (string) config('moonshine.user_fields.username', 'email');
        $username = Str::transliterate(Str::lower((string) $request->input($usernameField, '')));

        return 'moonshine-login:'.$username.'|'.$request->ip();
    }
}
