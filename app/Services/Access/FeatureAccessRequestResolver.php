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
            consume: $this->shouldConsume($request, $policy),
            counts: $policy->counts,
        );
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

    private function shouldConsume(Request $request, AccessResourcePolicy $policy): bool
    {
        if (! $policy->counts) {
            return false;
        }

        return (string) $request->query('snapshotRole', '') !== 'previous';
    }
}
