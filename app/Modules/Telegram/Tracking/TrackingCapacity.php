<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTracking;
use App\Models\User;

final readonly class TrackingCapacity
{
    public function __construct(private TrackingConfig $config, private TrackingLifecycle $lifecycle) {}

    /** @return array{limit: int, active_count: int, remaining: int} */
    public function forUser(User $user): array
    {
        $this->lifecycle->synchronize($user->id);
        $limit = $this->config->limit($user->currentPlan());
        $active = $this->activeCount($user);

        return ['limit' => $limit, 'active_count' => $active, 'remaining' => max(0, $limit - $active)];
    }

    public function activeCount(User $user): int
    {
        return TelegramTracking::query()->forUser($user->id)->unfinished()->active()
            ->where('expires_at', '>', now())->count();
    }
}
