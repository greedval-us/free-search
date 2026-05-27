<?php

namespace App\Services\Access\Contracts;

use App\Models\User;

interface FeatureUsageCounterInterface
{
    public function usedToday(User $user, string $quotaKey): int;

    public function consume(User $user, string $quotaKey, int $limit): ?int;
}
