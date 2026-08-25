<?php

namespace App\Services\Access;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Services\Access\Contracts\FeatureUsageCounterInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class FeatureUsageCounter implements FeatureUsageCounterInterface
{
    public function usedToday(User $user, string $quotaKey): int
    {
        return (int) FeatureUsageDaily::query()
            ->where('user_id', $user->id)
            ->where('feature', $quotaKey)
            ->where('usage_date', $this->usageDate())
            ->value('used');
    }

    public function consume(User $user, string $quotaKey, int $limit): ?int
    {
        return DB::transaction(function () use ($user, $quotaKey, $limit): ?int {
            $usageDate = $this->usageDate();
            $now = now();

            FeatureUsageDaily::query()->insertOrIgnore([
                'user_id' => $user->id,
                'feature' => $quotaKey,
                'usage_date' => $usageDate,
                'used' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $usage = FeatureUsageDaily::query()
                ->where('user_id', $user->id)
                ->where('feature', $quotaKey)
                ->where('usage_date', $usageDate)
                ->lockForUpdate()
                ->firstOrFail();

            if ($usage->used >= $limit) {
                return null;
            }

            $used = $usage->used + 1;
            $usage->forceFill(['used' => $used])->save();

            return $used;
        }, 3);
    }

    public function release(User $user, string $quotaKey): void
    {
        DB::transaction(function () use ($user, $quotaKey): void {
            $usage = FeatureUsageDaily::query()
                ->where('user_id', $user->id)
                ->where('feature', $quotaKey)
                ->where('usage_date', $this->usageDate())
                ->lockForUpdate()
                ->first();

            if ($usage === null || $usage->used <= 0) {
                return;
            }

            $usage->forceFill(['used' => $usage->used - 1])->save();
        }, 3);
    }

    private function usageDate(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))->startOfDay();
    }
}
