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

        $resource = $this->requestedPageResource($routeName, $request);
        if ($resource === null && ! $this->isProtectedRoute($routeName)) {
            return $next($request);
        }

        $routePolicy = $this->protectedRoutePolicy($routeName);
        $decision = $resource !== null || $routePolicy === null || ! $this->shouldConsume($request, $routePolicy)
            ? $this->featureAccessService->inspect(
                $user,
                $resource ?? (string) ($routePolicy['resource'] ?? $routePolicy['feature'] ?? 'analytics'),
                $resource !== null || (bool) ($routePolicy['counts'] ?? true),
            )
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

    private function requestedPageResource(string $routeName, Request $request): ?string
    {
        if (! in_array($routeName, ['telegram', 'youtube', 'site-intel'], true)) {
            return null;
        }

        $tab = (string) $request->query('tab', '');
        if ($routeName === 'site-intel' && $tab === 'seoAudit') {
            return 'site-intel.seo-audit';
        }

        if (! in_array($tab, ['analytics', 'parser'], true)) {
            return null;
        }

        return "{$routeName}.{$tab}";
    }

    private function isProtectedRoute(string $routeName): bool
    {
        return $this->protectedRoutePolicy($routeName) !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function protectedRoutePolicy(string $routeName): ?array
    {
        $routes = config('access.protected_routes', []);
        if (! is_array($routes) || ! array_key_exists($routeName, $routes)) {
            return null;
        }

        $policy = $routes[$routeName];

        return is_array($policy) ? $policy : null;
    }

    /**
     * @param  array<string, mixed>  $routePolicy
     */
    private function shouldConsume(Request $request, array $routePolicy): bool
    {
        if (! (bool) ($routePolicy['counts'] ?? true)) {
            return false;
        }

        return (string) $request->query('snapshotRole', '') !== 'previous';
    }
}
