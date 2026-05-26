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

        $feature = $this->requestedPageFeature($routeName, $request);
        if ($feature === null && ! $this->isProtectedRoute($routeName)) {
            return $next($request);
        }

        $decision = $feature !== null
            ? $this->featureAccessService->inspect($user, $feature)
            : $this->featureAccessService->consume($user, $routeName);

        if ($decision->allowed) {
            return $next($request);
        }

        $status = $decision->limit <= 0 ? Response::HTTP_FORBIDDEN : Response::HTTP_TOO_MANY_REQUESTS;
        $message = $decision->message ?? __('Feature access denied.');
        $request->attributes->set('feature_access_denied', true);

        if ($request->expectsJson()) {
            return new JsonResponse([
                'ok' => false,
                'message' => $message,
                'meta' => $decision->toMeta(),
            ], $status);
        }

        return redirect()->route('billing.edit', [
            'feature' => $decision->feature,
            'reason' => $decision->limit <= 0 ? 'plan' : 'quota',
        ]);
    }

    private function requestedPageFeature(string $routeName, Request $request): ?string
    {
        if (! in_array($routeName, ['telegram', 'youtube', 'site-intel'], true)) {
            return null;
        }

        $tab = (string) $request->query('tab', '');

        return in_array($tab, ['analytics', 'parser'], true) ? $tab : null;
    }

    private function isProtectedRoute(string $routeName): bool
    {
        $routes = config('access.protected_routes', []);

        return is_array($routes) && array_key_exists($routeName, $routes);
    }
}
