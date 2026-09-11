<?php

namespace Tests\Feature;

use App\Models\TelegramTracking;
use App\Models\User;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\Telegram\Tracking\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeTrackingGateway;
use Tests\TestCase;

class DashboardTrackingCapacityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['inertia.ssr.enabled' => false]);
        Http::preventStrayRequests();
        config(['telegram_tracking.queue.connection' => 'database', 'telegram_bot.enabled' => false]);
        $this->app->instance(TrackingGateway::class, new FakeTrackingGateway);
    }

    public static function plans(): array
    {
        return [['free', 1], ['plus', 3], ['pro', 5]];
    }

    #[DataProvider('plans')]
    public function test_dashboard_and_tracking_list_share_current_capacity(string $plan, int $limit): void
    {
        $user = User::factory()->create();
        if ($plan !== 'free') {
            $user->subscriptions()->create(['plan' => $plan, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]);
        }
        $this->createTracking($user);
        $this->assertCapacity($user, $limit, 1);
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public function test_counts_only_own_active_tasks_regardless_of_activity_filters(): void
    {
        $user = User::factory()->create();
        config(['telegram_tracking.limits.free' => 4]);
        $this->createTracking(User::factory()->create());
        $paused = $this->createTracking($user);
        app(TrackingService::class)->change($user, $paused->id, 'pause');
        $stopped = $this->createTracking($user);
        app(TrackingService::class)->change($user, $stopped->id, 'stop');
        $this->createTracking($user);

        $this->assertCapacity($user, 4, 1);
        $this->get(route('dashboard', ['module_key' => 'youtube', 'period' => '7d', 'query' => 'unrelated']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('tracking', ['limit' => 4, 'active_count' => 1, 'remaining' => 3]));

        app(TrackingService::class)->change($user, $paused->id, 'resume');
        $this->assertCapacity($user, 4, 2);
    }

    public function test_dashboard_expires_overdue_tasks_before_counting(): void
    {
        $user = User::factory()->create();
        $task = $this->createTracking($user);
        $this->travelTo($task->expires_at);

        $this->assertCapacity($user, 1, 0);
        $this->assertSame(TelegramTracking::EXPIRED, $task->fresh()->status);
    }

    public function test_dashboard_pauses_paid_tasks_after_subscription_ends(): void
    {
        $user = User::factory()->create();
        $user->subscriptions()->create(['plan' => 'plus', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDay()]);
        $task = $this->createTracking($user);
        $this->travel(2)->days();

        $this->assertCapacity($user, 1, 0);
        $this->assertSame(TelegramTracking::PAUSED, $task->fresh()->status);
        $this->assertSame('subscription_expired', $task->fresh()->pause_reason);
        $this->assertSame(1, $user->notifications()->count());
    }

    private function createTracking(User $user): TelegramTracking
    {
        return app(TrackingService::class)->create($user, ['name' => 'Test task', 'mode' => 'keyword', 'query' => 'test', 'groups' => ['publicgroup'], 'notify_bot' => false]);
    }

    private function assertCapacity(User $user, int $limit, int $active): void
    {
        $expected = ['limit' => $limit, 'active_count' => $active, 'remaining' => $limit - $active];
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard')->where('tracking', $expected));
        $this->getJson('/telegram/tracking')->assertOk()->assertJson($expected);
    }
}
