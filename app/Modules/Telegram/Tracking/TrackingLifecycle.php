<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTracking;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Access\Enums\AccountPlan;
use Illuminate\Support\Facades\DB;

final readonly class TrackingLifecycle
{
    public function __construct(private TrackingConfig $config, private TrackingNotifications $notifications) {}

    public function synchronize(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            $user = User::query()->lockForUpdate()->find($userId);
            if ($user !== null) {
                $this->synchronizeLocked($user);
            }
        });
    }

    // All mutation paths acquire the user row before tasks and sources.
    public function synchronizeLocked(User $user): void
    {
        $subscription = $user->activeSubscription()->first();
        $plan = AccountPlan::fromNullable($subscription?->plan);
        $active = 0;
        $tasks = TelegramTracking::query()->forUser($user->id)->unfinished()->orderBy('id')->lockForUpdate()->get();
        foreach ($tasks as $task) {
            if ($task->expires_at->lte(now())) {
                $this->end($task, TelegramTracking::EXPIRED);

                continue;
            }
            if ($task->status !== TelegramTracking::ACTIVE) {
                continue;
            }
            $reason = $this->synchronizeEntitlement($user, $task, $subscription);
            if ($reason === null && ++$active > $this->config->limit($plan)) {
                $reason = 'plan_limit';
            }
            if ($reason !== null) {
                $this->pause($task, $reason);
                $this->notifications->paused($user, $task);
            }
        }
    }

    private function synchronizeEntitlement(User $user, TelegramTracking $task, ?UserSubscription $subscription): ?string
    {
        if ($user->isBlocked() || ! $user->hasVerifiedEmail()) {
            return 'account_unavailable';
        }
        if ($task->activation_plan === AccountPlan::Free->value) {
            return null;
        }
        if ($subscription === null || ($task->entitlement_until?->lte(now())
            && $subscription->starts_at->gt($task->entitlement_until))) {
            return 'subscription_expired';
        }
        if ($task->entitlement_until === null || $subscription->ends_at->gt($task->entitlement_until)) {
            $task->update(['entitlement_until' => $subscription->ends_at]);
        }

        return null;
    }

    public function pause(TelegramTracking $task, string $reason = 'manual'): void
    {
        $task->update(['status' => TelegramTracking::PAUSED, 'pause_reason' => $reason]);
        $this->invalidate($task);
    }

    public function end(TelegramTracking $task, string $status = TelegramTracking::STOPPED): void
    {
        $end = $status === TelegramTracking::EXPIRED ? $task->expires_at : now();
        $task->update(['status' => $status, 'ended_at' => $end,
            'purge_at' => $end->addDays($this->config->integer('retention_days')), 'pause_reason' => null]);
        $this->invalidate($task);
    }

    private function invalidate(TelegramTracking $task): void
    {
        $task->sources()->update(['lease_token' => null, 'lease_until' => null]);
    }
}
