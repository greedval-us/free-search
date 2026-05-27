<?php

namespace App\Services\Access;

use App\Services\Access\Contracts\AccessPolicyResolverInterface;
use App\Services\Access\Contracts\FeatureAccessRequestResolverInterface;
use App\Services\Access\DTO\FeatureAccessRequest;
use Illuminate\Http\Request;

final readonly class FeatureAccessRequestResolver implements FeatureAccessRequestResolverInterface
{
    public function __construct(
        private AccessPolicyResolverInterface $policyResolver,
    ) {}

    public function resolve(Request $request): ?FeatureAccessRequest
    {
        $routeName = $request->route()?->getName();
        if (! is_string($routeName)) {
            return null;
        }

        $pageResource = $this->requestedPageResource($routeName, $request);
        if ($pageResource !== null) {
            return new FeatureAccessRequest(
                resource: $pageResource,
                consume: false,
                counts: true,
            );
        }

        $policy = $this->policyResolver->routePolicy($routeName);
        if ($policy === null) {
            return null;
        }

        $hasNonCountingQueryValue = $this->hasNonCountingQueryValue($request, $routeName);

        return new FeatureAccessRequest(
            resource: $policy->resource,
            consume: $this->shouldConsume($policy, $hasNonCountingQueryValue),
            counts: $this->shouldCount($policy, $hasNonCountingQueryValue),
        );
    }

    private function requestedPageResource(string $routeName, Request $request): ?string
    {
        $pageResources = config('access.page_resources', []);
        if (! is_array($pageResources)) {
            return null;
        }

        $pageConfig = $pageResources[$routeName] ?? null;
        if (! is_array($pageConfig)) {
            return null;
        }

        $tabs = $pageConfig['tabs'] ?? [];
        if (! is_array($tabs)) {
            return null;
        }

        $resource = $tabs[(string) $request->query('tab', '')] ?? null;

        return is_string($resource) && $resource !== '' ? $resource : null;
    }

    private function shouldConsume(AccessResourcePolicy $policy, bool $hasNonCountingQueryValue): bool
    {
        if (! $policy->counts) {
            return false;
        }

        return ! $hasNonCountingQueryValue;
    }

    private function shouldCount(AccessResourcePolicy $policy, bool $hasNonCountingQueryValue): bool
    {
        if (! $policy->counts) {
            return false;
        }

        return ! $hasNonCountingQueryValue;
    }

    private function hasNonCountingQueryValue(Request $request, string $routeName): bool
    {
        return $this->matchesNonCountingQueryValues(
            $request,
            config('access.non_counting_query_values', [])
        ) || $this->matchesNonCountingQueryValues(
            $request,
            $this->scopedQueryValuesForRoute($routeName)
        );
    }

    /**
     * @param  mixed  $queryValues
     */
    private function matchesNonCountingQueryValues(Request $request, mixed $queryValues): bool
    {
        if (! is_array($queryValues)) {
            return false;
        }

        foreach ($queryValues as $key => $values) {
            if (! is_string($key) || ! is_array($values)) {
                continue;
            }

            if (in_array((string) $request->query($key, ''), $values, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function scopedQueryValuesForRoute(string $routeName): array
    {
        $scoped = config('access.non_counting_query_values_scoped', []);
        if (! is_array($scoped)) {
            return [];
        }

        $routeScoped = $scoped[$routeName] ?? [];

        return is_array($routeScoped) ? $routeScoped : [];
    }
}
