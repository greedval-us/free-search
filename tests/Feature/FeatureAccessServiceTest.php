<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Feature\Concerns\ControlsTestTime;
use Tests\Feature\Concerns\CreatesSubscribedUser;
use Tests\TestCase;

class FeatureAccessServiceTest extends TestCase
{
    use ControlsTestTime;
    use CreatesSubscribedUser;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->tearDownControlsTestTime();

        parent::tearDown();
    }

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
        $user = $this->createSubscribedUser();

        $decision = $this->service()->consume($user, 'telegram.analytics.summary');

        $this->assertTrue($decision->allowed);
        $this->assertSame('telegram.analytics', $decision->feature);
        $this->assertSame('plus', $decision->plan);
        $this->assertSame(10, $decision->limit);
        $this->assertSame(1, $decision->used);
        $this->assertSame(1, $this->usage($user, 'telegram.analytics'));
        $this->assertSame(0, $this->usage($user, 'analytics'));
    }

    public function test_site_intel_seo_audit_uses_own_quota_pool(): void
    {
        $user = $this->createSubscribedUser();

        $decision = $this->service()->consume($user, 'site-intel.seo-audit');

        $this->assertTrue($decision->allowed);
        $this->assertSame('site-intel.seo-audit', $decision->feature);
        $this->assertSame(10, $decision->limit);
        $this->assertSame(1, $this->usage($user, 'site-intel.seo-audit'));
        $this->assertSame(0, $this->usage($user, 'site-intel.analytics'));
    }

    public function test_module_specific_quota_override_can_be_configured(): void
    {
        Config::set('access.plans.plus', [
            ...config('access.plans.plus'),
            'youtube.analytics' => 2,
        ]);

        $user = User::factory()->create();
        $this->attachSubscription($user);

        $this->assertTrue($this->service()->consume($user, 'youtube.analytics.summary')->allowed);
        $this->assertTrue($this->service()->consume($user, 'youtube.analytics.summary')->allowed);

        $decision = $this->service()->consume($user, 'youtube.analytics.summary');

        $this->assertFalse($decision->allowed);
        $this->assertSame(2, $decision->limit);
        $this->assertSame(2, $this->usage($user, 'youtube.analytics'));
        $this->assertSame(0, $this->usage($user, 'analytics'));
    }

    public function test_plus_subscription_stops_after_daily_parser_limit(): void
    {
        $user = $this->createSubscribedUser();

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($this->service()->consume($user, 'youtube.parser.comments')->allowed);
        }

        $decision = $this->service()->consume($user, 'youtube.parser.comments');

        $this->assertFalse($decision->allowed);
        $this->assertSame(5, $decision->limit);
        $this->assertSame(5, $decision->used);
        $this->assertSame(5, $this->usage($user, 'youtube.parser'));
        $this->assertSame(0, $this->usage($user, 'telegram.parser'));
        $this->assertTrue($this->service()->consume($user, 'telegram.parser.start')->allowed);
    }

    public function test_daily_quota_is_available_again_on_the_next_day_without_a_job(): void
    {
        $this->freezeNow('2026-05-27 10:00:00');

        $user = $this->createSubscribedUser();

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($this->service()->consume($user, 'youtube.parser.comments')->allowed);
        }

        $this->assertFalse($this->service()->consume($user, 'youtube.parser.comments')->allowed);
        $this->assertSame(5, $this->usage($user, 'youtube.parser', '2026-05-27'));

        $this->freezeNow('2026-05-28 00:01:00');

        $decision = $this->service()->consume($user, 'youtube.parser.comments');

        $this->assertTrue($decision->allowed);
        $this->assertSame(1, $decision->used);
        $this->assertSame(5, $this->usage($user, 'youtube.parser', '2026-05-27'));
        $this->assertSame(1, $this->usage($user, 'youtube.parser', '2026-05-28'));
    }

    public function test_parser_status_requires_paid_plan_without_consuming_quota(): void
    {
        $user = $this->createSubscribedUser();

        $decision = $this->service()->consume($user, 'telegram.parser.status');

        $this->assertTrue($decision->allowed);
        $this->assertFalse($decision->counts);
        $this->assertSame(0, $this->usage($user, 'telegram.parser'));
    }

    public function test_bluesky_parser_start_uses_its_own_quota_pool(): void
    {
        $user = $this->createSubscribedUser();

        $decision = $this->service()->consume($user, 'bluesky.parser.start');

        $this->assertTrue($decision->allowed);
        $this->assertSame('bluesky.parser', $decision->feature);
        $this->assertSame(5, $decision->limit);
        $this->assertSame(1, $this->usage($user, 'bluesky.parser'));
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

    private function usage(User $user, string $feature, ?string $date = null): int
    {
        $query = FeatureUsageDaily::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature);

        if ($date !== null) {
            $query->whereDate('usage_date', $date);
        }

        return (int) $query
            ->value('used');
    }
}
