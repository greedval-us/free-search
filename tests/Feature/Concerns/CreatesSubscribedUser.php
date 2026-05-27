<?php

namespace Tests\Feature\Concerns;

use App\Models\User;
use App\Models\UserSubscription;

trait CreatesSubscribedUser
{
    private function createSubscribedUser(string $plan = User::SUBSCRIPTION_PLAN_PLUS): User
    {
        $user = User::factory()->create();

        $this->attachSubscription($user, $plan);

        return $user;
    }

    private function attachSubscription(User $user, string $plan = User::SUBSCRIPTION_PLAN_PLUS): void
    {
        UserSubscription::query()->create([
            'user_id' => $user->id,
            'plan' => $plan,
            'status' => UserSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);
    }
}
