<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

final class EnsureResendWebhookConfigured
{
    public function __construct(
        private readonly ThrottleRequests $throttleRequests,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = trim((string) config('resend.path', 'resend'), '/').'/webhook';

        if (! $request->isMethod('POST') || ! $request->is($path)) {
            return $next($request);
        }

        abort_unless(filled(config('resend.webhook.secret')), Response::HTTP_NOT_FOUND);

        return $this->throttleRequests->handle($request, $next, 'resend-webhook');
    }
}
