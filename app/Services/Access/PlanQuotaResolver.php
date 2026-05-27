<?php

namespace App\Services\Access;

use App\Services\Access\Contracts\PlanQuotaResolverInterface;
use App\Support\Access\AccountPlan;
use Illuminate\Support\Arr;

final class PlanQuotaResolver implements PlanQuotaResolverInterface
{
    public function limitFor(AccountPlan $plan, AccessResourcePolicy $policy): int
    {
        $plans = config('access.plans', []);
        $planLimits = is_array($plans) ? ($plans[$plan->value] ?? []) : [];
        $limit = is_array($planLimits)
            ? ($planLimits[$policy->resource]
                ?? Arr::get($planLimits, $policy->resource)
                ?? $planLimits[$policy->quotaKey]
                ?? Arr::get($planLimits, $policy->quotaKey)
                ?? 0)
            : 0;

        return max(0, (int) $limit);
    }
}
