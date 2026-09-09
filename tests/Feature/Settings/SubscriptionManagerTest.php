<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Subscriptions\SubscriptionManager;
use App\Support\Access\Enums\AccountPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_plan_update_is_idempotent_and_notifies_only_when_changed(): void
    {
        $user = User::factory()->create();
        $manager = app(SubscriptionManager::class);
        $first = $manager->replace($user, AccountPlan::Plus, ['source' => 'moonshine'], keepSamePlan: true);
        $again = $manager->replace($user, AccountPlan::Plus, ['source' => 'moonshine'], keepSamePlan: true);
        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, $user->notifications()->count());
        $next = $manager->replace($user, AccountPlan::Pro, ['source' => 'moonshine'], keepSamePlan: true);
        $this->assertSame(UserSubscription::STATUS_CANCELED, $first->fresh()->status);
        $this->assertSame(AccountPlan::Pro->value, $next->plan);
        $this->assertSame(1, $user->subscriptions()->where('status', UserSubscription::STATUS_ACTIVE)->count());
        $this->assertSame(2, $user->notifications()->count());
    }

    public function test_free_plan_cancels_active_subscription_without_creating_a_paid_one(): void
    {
        $user = User::factory()->create();
        $manager = app(SubscriptionManager::class);
        $subscription = $manager->replace($user, AccountPlan::Pro, ['source' => 'moonshine']);
        $this->assertNull($manager->replace($user, AccountPlan::Free, ['source' => 'moonshine']));
        $this->assertSame(UserSubscription::STATUS_CANCELED, $subscription->fresh()->status);
        $this->assertSame(AccountPlan::Free, $user->fresh()->currentPlan());
    }
}
