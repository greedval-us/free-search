<?php

namespace App\Services\Access\Contracts;

use App\Models\User;
use App\Services\Access\DTO\FeatureUsageReceipt;

interface FeatureUsageCounterInterface
{
    public function usedToday(User $user, string $quotaKey): int;

    public function consume(User $user, string $quotaKey, int $limit): ?FeatureUsageReceipt;

    public function release(User $user, string $receiptId): void;
}
