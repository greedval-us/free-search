<?php

namespace App\Services\Access;

use App\Models\User;
use App\Services\Access\Contracts\AccessPolicyResolverInterface;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use App\Services\Access\Contracts\FeatureUsageCounterInterface;
use App\Services\Access\Contracts\PlanQuotaResolverInterface;
use App\Services\Access\DTO\FeatureAccessDecision;
use App\Support\Access\Enums\AccountPlan;

final class FeatureAccessService implements FeatureAccessServiceInterface
{
    public function __construct(
        private readonly AccessPolicyResolverInterface $policyResolver,
        private readonly FeatureUsageCounterInterface $usageCounter,
        private readonly PlanQuotaResolverInterface $quotaResolver,
    ) {}

    public function consume(User $user, string $routeName): FeatureAccessDecision
    {
        $policy = $this->policyResolver->routePolicy($routeName)
            ?? $this->policyResolver->resourcePolicy('analytics');

        return $this->decide($user, $policy, true);
    }

    public function inspect(User $user, string $resource, bool $counts = true): FeatureAccessDecision
    {
        return $this->decide($user, $this->policyResolver->resourcePolicy($resource, $counts), false);
    }

    private function decide(User $user, AccessResourcePolicy $policy, bool $consume): FeatureAccessDecision
    {
        if ($this->policyResolver->canBypass($user)) {
            return new FeatureAccessDecision(
                allowed: true,
                feature: $policy->resource,
                plan: 'staff',
                limit: PHP_INT_MAX,
                used: 0,
                counts: $policy->counts,
            );
        }

        $plan = $user->currentPlan();
        $limit = $this->quotaResolver->limitFor($plan, $policy);

        if ($limit <= 0) {
            return $this->deny(
                feature: $policy->resource,
                plan: $plan,
                limit: $limit,
                used: 0,
                counts: $policy->counts,
                message: __('This feature is available on Plus and Pro plans.')
            );
        }

        if (! $consume) {
            $used = $this->usageCounter->usedToday($user, $policy->quotaKey);

            if ($policy->counts && $used >= $limit) {
                return $this->deny(
                    feature: $policy->resource,
                    plan: $plan,
                    limit: $limit,
                    used: $used,
                    counts: $policy->counts,
                    message: __('Daily limit reached for this feature.')
                );
            }

            return new FeatureAccessDecision(
                allowed: true,
                feature: $policy->resource,
                plan: $plan->value,
                limit: $limit,
                used: $used,
                counts: $policy->counts,
            );
        }

        if (! $policy->counts) {
            return new FeatureAccessDecision(
                allowed: true,
                feature: $policy->resource,
                plan: $plan->value,
                limit: $limit,
                used: $this->usageCounter->usedToday($user, $policy->quotaKey),
                counts: false,
            );
        }

        $used = $this->usageCounter->consume($user, $policy->quotaKey, $limit);

        if ($used === null) {
            return $this->deny(
                feature: $policy->resource,
                plan: $plan,
                limit: $limit,
                used: $this->usageCounter->usedToday($user, $policy->quotaKey),
                counts: $policy->counts,
                message: __('Daily limit reached for this feature.')
            );
        }

        return new FeatureAccessDecision(
            allowed: true,
            feature: $policy->resource,
            plan: $plan->value,
            limit: $limit,
            used: $used,
            counts: $policy->counts,
        );
    }

    private function deny(
        string $feature,
        AccountPlan $plan,
        int $limit,
        int $used,
        bool $counts,
        string $message,
    ): FeatureAccessDecision {
        return new FeatureAccessDecision(
            allowed: false,
            feature: $feature,
            plan: $plan->value,
            limit: $limit,
            used: $used,
            counts: $counts,
            message: $message,
        );
    }
}
