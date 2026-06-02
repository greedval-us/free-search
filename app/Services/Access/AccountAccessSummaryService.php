<?php

namespace App\Services\Access;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Access\Enums\AccountPlan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

final class AccountAccessSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(?User $user): array
    {
        if ($user === null) {
            return [
                'plan' => AccountPlan::Free->value,
                'subscription' => null,
                'features' => $this->featureSummaries(AccountPlan::Free, []),
            ];
        }

        $subscription = $user->activeSubscription()->first();
        $plan = AccountPlan::fromNullable($subscription?->plan);
        $usage = $this->usageForToday($user);

        return [
            'plan' => $plan->value,
            'subscription' => $subscription instanceof UserSubscription ? [
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at?->toISOString(),
                'ends_at' => $subscription->ends_at?->toISOString(),
            ] : null,
            'features' => $this->featureSummaries($plan, $usage),
        ];
    }

    /**
     * @param  array<string, int>  $usage
     * @return array<string, array{limit: int, used: int, remaining: int, allowed: bool}>
     */
    private function featureSummaries(AccountPlan $plan, array $usage): array
    {
        $plans = config('access.plans', []);
        $planLimits = is_array($plans) ? ($plans[$plan->value] ?? []) : [];
        $features = $this->summaryFeatures();
        $result = [];

        foreach ($features as $feature => $quotaKey) {
            $limit = max(0, (int) (
                $planLimits[$feature]
                ?? Arr::get($planLimits, $feature)
                ?? $planLimits[$quotaKey]
                ?? Arr::get($planLimits, $quotaKey)
                ?? 0
            ));
            $used = max(0, (int) ($usage[$quotaKey] ?? 0));

            $result[$feature] = [
                'limit' => $limit,
                'used' => $used,
                'remaining' => max(0, $limit - $used),
                'allowed' => $limit > 0 && $used < $limit,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function summaryFeatures(): array
    {
        $features = [
            'analytics' => 'analytics',
            'parser' => 'parser',
        ];

        $resources = config('access.resources', []);
        if (! is_array($resources)) {
            return $features;
        }

        foreach ($resources as $resource => $config) {
            if (! is_array($config)) {
                continue;
            }

            $features[(string) $resource] = (string) ($config['quota_key'] ?? $resource);
        }

        return $features;
    }

    /**
     * @return array<string, int>
     */
    private function usageForToday(User $user): array
    {
        return FeatureUsageDaily::query()
            ->where('user_id', $user->id)
            ->where('usage_date', CarbonImmutable::now(config('app.timezone'))->startOfDay())
            ->pluck('used', 'feature')
            ->map(static fn (mixed $value): int => (int) $value)
            ->toArray();
    }
}
