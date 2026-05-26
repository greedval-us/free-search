<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_account_cannot_use_analytics(): void
    {
        $user = User::factory()->create();

        $decision = $this->service()->consume($user, 'site-intel.analytics');

        $this->assertFalse($decision->allowed);
        $this->assertSame('free', $decision->plan);
        $this->assertSame(0, $decision->limit);
    }

    public function test_plus_subscription_consumes_analytics_quota(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, User::SUBSCRIPTION_PLAN_PLUS);

        $decision = $this->service()->consume($user, 'telegram.analytics.summary');

        $this->assertTrue($decision->allowed);
        $this->assertSame('plus', $decision->plan);
        $this->assertSame(10, $decision->limit);
        $this->assertSame(1, $decision->used);
        $this->assertSame(1, $this->usage($user, 'analytics'));
    }

    public function test_plus_subscription_stops_after_daily_parser_limit(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, User::SUBSCRIPTION_PLAN_PLUS);

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($this->service()->consume($user, 'youtube.parser.comments')->allowed);
        }

        $decision = $this->service()->consume($user, 'telegram.parser.start');

        $this->assertFalse($decision->allowed);
        $this->assertSame(5, $decision->limit);
        $this->assertSame(5, $decision->used);
    }

    public function test_parser_status_requires_paid_plan_without_consuming_quota(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, User::SUBSCRIPTION_PLAN_PLUS);

        $decision = $this->service()->consume($user, 'telegram.parser.status');

        $this->assertTrue($decision->allowed);
        $this->assertFalse($decision->counts);
        $this->assertSame(0, $this->usage($user, 'parser'));
    }

    public function test_expired_subscription_falls_back_to_free(): void
    {
        $user = User::factory()->create();
        UserSubscription::query()->create([
            'user_id' => $user->id,
            'plan' => User::SUBSCRIPTION_PLAN_PRO,
            'status' => UserSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
        ]);

        $decision = $this->service()->consume($user, 'youtube.analytics.summary');

        $this->assertFalse($decision->allowed);
        $this->assertSame('free', $decision->plan);
    }

    private function service(): FeatureAccessServiceInterface
    {
        return $this->app->make(FeatureAccessServiceInterface::class);
    }

    private function subscribe(User $user, string $plan): void
    {
        UserSubscription::query()->create([
            'user_id' => $user->id,
            'plan' => $plan,
            'status' => UserSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    private function usage(User $user, string $feature): int
    {
        return (int) FeatureUsageDaily::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature)
            ->value('used');
    }
}
