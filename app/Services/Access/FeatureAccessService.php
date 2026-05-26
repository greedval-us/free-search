<?php

namespace App\Services\Access;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use App\Services\Access\DTO\FeatureAccessDecision;
use App\Support\Access\AccountPlan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class FeatureAccessService implements FeatureAccessServiceInterface
{
    public function consume(User $user, string $routeName): FeatureAccessDecision
    {
        $policy = $this->routePolicy($routeName);
        $feature = $policy['feature'];
        $counts = $policy['counts'];

        if ($this->shouldBypass($user)) {
            return new FeatureAccessDecision(
                allowed: true,
                feature: $feature,
                plan: 'staff',
                limit: PHP_INT_MAX,
                used: 0,
                counts: false,
            );
        }

        $plan = $user->currentPlan();
        $limit = $this->limitFor($plan, $feature);

        if ($limit <= 0) {
            return $this->deny(
                feature: $feature,
                plan: $plan,
                limit: $limit,
                used: 0,
                counts: $counts,
                message: __('This feature is available on Plus and Pro plans.')
            );
        }

        if (! $counts) {
            return new FeatureAccessDecision(
                allowed: true,
                feature: $feature,
                plan: $plan->value,
                limit: $limit,
                used: $this->usedToday($user, $feature),
                counts: false,
            );
        }

        return DB::transaction(function () use ($user, $feature, $plan, $limit, $counts): FeatureAccessDecision {
            $usage = FeatureUsageDaily::query()
                ->where('user_id', $user->id)
                ->where('feature', $feature)
                ->where('usage_date', $this->usageDate())
                ->lockForUpdate()
                ->first();

            if ($usage === null) {
                $usage = FeatureUsageDaily::query()->create([
                    'user_id' => $user->id,
                    'feature' => $feature,
                    'usage_date' => $this->usageDate(),
                    'used' => 0,
                ]);
            }

            if ($usage->used >= $limit) {
                return $this->deny(
                    feature: $feature,
                    plan: $plan,
                    limit: $limit,
                    used: $usage->used,
                    counts: $counts,
                    message: __('Daily limit reached for this feature.')
                );
            }

            $used = $usage->used + 1;
            $usage->forceFill(['used' => $used])->save();

            return new FeatureAccessDecision(
                allowed: true,
                feature: $feature,
                plan: $plan->value,
                limit: $limit,
                used: $used,
                counts: $counts,
            );
        });
    }

    /**
     * @return array{feature: string, counts: bool}
     */
    private function routePolicy(string $routeName): array
    {
        $routes = config('access.protected_routes', []);
        $policy = is_array($routes) ? ($routes[$routeName] ?? []) : [];

        return [
            'feature' => (string) ($policy['feature'] ?? 'analytics'),
            'counts' => (bool) ($policy['counts'] ?? true),
        ];
    }

    private function shouldBypass(User $user): bool
    {
        $accountTypes = config('access.bypass_account_types', []);

        return is_array($accountTypes)
            && in_array((string) $user->account_type, $accountTypes, true);
    }

    private function limitFor(AccountPlan $plan, string $feature): int
    {
        $plans = config('access.plans', []);
        $limit = is_array($plans) ? ($plans[$plan->value][$feature] ?? 0) : 0;

        return max(0, (int) $limit);
    }

    private function usedToday(User $user, string $feature): int
    {
        return (int) FeatureUsageDaily::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature)
            ->where('usage_date', $this->usageDate())
            ->value('used');
    }

    private function usageDate(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))->startOfDay();
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
