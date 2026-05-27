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
            $usage = FeatureUsageDaily::query()
                ->where('user_id', $user->id)
                ->where('feature', $quotaKey)
                ->where('usage_date', $this->usageDate())
                ->lockForUpdate()
                ->first();

            if ($usage === null) {
                $usage = FeatureUsageDaily::query()->create([
                    'user_id' => $user->id,
                    'feature' => $quotaKey,
                    'usage_date' => $this->usageDate(),
                    'used' => 0,
                ]);
            }

            if ($usage->used >= $limit) {
                return null;
            }

            $used = $usage->used + 1;
            $usage->forceFill(['used' => $used])->save();

            return $used;
        });
    }

    private function usageDate(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))->startOfDay();
    }
}
