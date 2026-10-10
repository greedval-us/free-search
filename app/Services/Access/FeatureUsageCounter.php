<?php

namespace App\Services\Access;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Services\Access\Contracts\FeatureUsageCounterInterface;
use App\Services\Access\DTO\FeatureUsageReceipt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function consume(User $user, string $quotaKey, int $limit): ?FeatureUsageReceipt
    {
        return DB::transaction(function () use ($user, $quotaKey, $limit): ?FeatureUsageReceipt {
            $usageDate = $this->usageDate();
            $now = now();

            // A duplicate upsert takes an exclusive row lock on InnoDB. INSERT
            // IGNORE would retain shared duplicate-key locks until FOR UPDATE.
            FeatureUsageDaily::query()->upsert([[
                'user_id' => $user->id,
                'feature' => $quotaKey,
                'usage_date' => $usageDate,
                'used' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]], ['user_id', 'feature', 'usage_date'], ['updated_at']);

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
            $receiptId = (string) Str::uuid();
            DB::table('feature_usage_receipts')->insert([
                'id' => $receiptId,
                'feature_usage_daily_id' => $usage->id,
                'created_at' => $now,
            ]);

            return new FeatureUsageReceipt($receiptId, $usageDate->toDateString(), $used);
        }, 3);
    }

    public function release(User $user, string $receiptId): void
    {
        DB::transaction(function () use ($user, $receiptId): void {
            $receipt = DB::table('feature_usage_receipts')->where('id', $receiptId)->first();
            if ($receipt === null) {
                return;
            }

            // Every debit/refund locks the daily counter before its receipt.
            $usage = FeatureUsageDaily::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->find($receipt->feature_usage_daily_id);

            if ($usage === null) {
                return;
            }

            $receipt = DB::table('feature_usage_receipts')->where('id', $receiptId)->lockForUpdate()->first();
            if ($receipt === null || $receipt->released_at !== null) {
                return;
            }

            DB::table('feature_usage_receipts')->where('id', $receiptId)->update(['released_at' => now()]);
            $usage->forceFill(['used' => max(0, $usage->used - 1)])->save();
        }, 3);
    }

    private function usageDate(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))->startOfDay();
    }
}
