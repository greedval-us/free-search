<?php

namespace Tests\Feature\Controllers\Concerns;

use App\Models\User;
use App\Models\UserSubscription;

trait CreatesPaidUser
{
    private function paidUser(): User
    {
        $user = User::factory()->create();

        UserSubscription::query()->create([
            'user_id' => $user->id,
            'plan' => User::SUBSCRIPTION_PLAN_PLUS,
            'status' => UserSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        return $user;
    }
}
