<?php

namespace App\Http\Middleware;

use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFeatureAccess
{
    public function __construct(private readonly FeatureAccessServiceInterface $featureAccessService) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $user = $request->user();

        if (! is_string($routeName) || $user === null) {
            return $next($request);
        }

        $decision = $this->featureAccessService->consume($user, $routeName);
        if ($decision->allowed) {
            return $next($request);
        }

        $status = $decision->limit <= 0 ? Response::HTTP_FORBIDDEN : Response::HTTP_TOO_MANY_REQUESTS;
        $message = $decision->message ?? __('Feature access denied.');

        if ($request->expectsJson() || $request->acceptsJson()) {
            return new JsonResponse([
                'ok' => false,
                'message' => $message,
                'meta' => $decision->toMeta(),
            ], $status);
        }

        return response($message, $status);
    }
}
