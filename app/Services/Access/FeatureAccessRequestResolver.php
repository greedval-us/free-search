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

        return new FeatureAccessRequest(
            resource: $policy->resource,
            consume: $policy->counts,
            counts: $policy->counts,
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
}
