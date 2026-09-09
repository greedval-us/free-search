<?php

namespace App\Services\Subscriptions;

use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Access\Enums\AccountPlan;
use App\Support\Notifications\UserNotificationService;
use Illuminate\Support\Facades\DB;

final readonly class SubscriptionManager
{
    public function __construct(private UserNotificationService $notifications) {}

    /** @param array<string, mixed> $metadata */
    public function replace(User $user, AccountPlan $plan, array $metadata, bool $keepSamePlan = false): ?UserSubscription
    {
        return DB::transaction(function () use ($user, $plan, $metadata, $keepSamePlan): ?UserSubscription {
            // Serialize all activation paths, including different tokens for the same user.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $active = $user->activeSubscription()->first();
            if ($keepSamePlan && $active?->plan === $plan->value) {
                return $active;
            }

            $now = now();
            $user->subscriptions()
                ->where('status', UserSubscription::STATUS_ACTIVE)
                ->where('ends_at', '>', $now)
                ->update(['status' => UserSubscription::STATUS_CANCELED, 'ends_at' => $now, 'updated_at' => $now]);
            $user->unsetRelation('activeSubscription');

            if ($plan === AccountPlan::Free) {
                return null;
            }

            $subscription = $user->subscriptions()->create([
                'plan' => $plan->value,
                'status' => UserSubscription::STATUS_ACTIVE,
                'starts_at' => $now,
                'ends_at' => $now->copy()->addMonth(),
                'metadata' => $metadata,
            ]);
            $this->notifications->sendSubscriptionActivated($user, $subscription, renewed: $active !== null);

            return $subscription;
        });
    }
}
