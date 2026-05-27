<?php

namespace App\Services\Access;

use App\Models\User;
use App\Services\Access\Contracts\AccessPolicyResolverInterface;

final class AccessPolicyResolver implements AccessPolicyResolverInterface
{
    public function routePolicy(string $routeName): ?AccessResourcePolicy
    {
        $routes = config('access.protected_routes', []);
        if (! is_array($routes) || ! array_key_exists($routeName, $routes)) {
            return null;
        }

        $policy = $routes[$routeName];
        if (! is_array($policy)) {
            return null;
        }

        return $this->resourcePolicy(
            (string) ($policy['resource'] ?? $policy['feature'] ?? 'analytics'),
            (bool) ($policy['counts'] ?? true),
        );
    }

    public function resourcePolicy(string $resource, bool $counts = true): AccessResourcePolicy
    {
        $resources = config('access.resources', []);
        $nestedResourceConfig = is_array($resources) ? data_get($resources, $resource) : null;
        $resourceConfig = is_array($resources)
            ? (is_array($nestedResourceConfig) ? $nestedResourceConfig : ($resources[$resource] ?? []))
            : [];

        return new AccessResourcePolicy(
            resource: $resource,
            quotaKey: (string) ($resourceConfig['quota_key'] ?? $resource),
            counts: $counts,
        );
    }

    public function canBypass(User $user): bool
    {
        $accountTypes = config('access.bypass_account_types', []);

        return is_array($accountTypes)
            && in_array((string) $user->account_type, $accountTypes, true);
    }
}
