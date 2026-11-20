<?php

namespace Tests\Feature\Concerns;

use App\Models\FeatureUsageDaily;
use App\Models\User;

trait AssertsUsedCredits
{
    private function assertUsedCredits(int $expected, User $user, ?string $date = null): void
    {
        $usage = FeatureUsageDaily::query()->where('user_id', $user->id);
        if ($date !== null) {
            $usage->whereDate('usage_date', $date);
        }

        $this->assertSame($expected, (int) $usage->sum('used'));
    }
}
