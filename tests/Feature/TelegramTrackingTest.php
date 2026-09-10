<?php

namespace Tests\Feature;

use App\Models\TelegramTracking;
use App\Models\TelegramTrackingSource;
use App\Models\User;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\Telegram\Tracking\Jobs\CollectTrackingSource;
use App\Modules\Telegram\Tracking\TrackingCollector;
use App\Modules\Telegram\Tracking\TrackingConfig;
use App\Modules\Telegram\Tracking\TrackingException;
use App\Modules\Telegram\Tracking\TrackingLifecycle;
use App\Modules\Telegram\Tracking\TrackingMatcher;
use App\Modules\Telegram\Tracking\TrackingReports;
use App\Modules\Telegram\Tracking\TrackingScheduler;
use App\Modules\Telegram\Tracking\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as Writer;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeTrackingGateway;
use Tests\TestCase;

class TelegramTrackingTest extends TestCase
{
    use RefreshDatabase;

    private FakeTrackingGateway $gateway;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 9, 10)->startOfDay());
        Http::preventStrayRequests();
        Bus::fake([CollectTrackingSource::class]);
        Cache::flush();
        config(['telegram_tracking.queue.connection' => 'database', 'telegram_bot.enabled' => false]);
        $this->gateway = new FakeTrackingGateway;
        $this->app->instance(TrackingGateway::class, $this->gateway);
        $this->user = User::factory()->create();
    }

    public function test_creates_validated_sources_and_restores_private_state(): void
    {
        $this->actingAs($this->user)->postJson('/telegram/tracking', $this->input(['groups' => ['@publicgroup', 'https://t.me/anothergroup']]))->assertCreated();
        $response = $this->getJson('/telegram/tracking')->assertOk()->assertJsonPath('limit', 1)->assertJsonPath('items.0.sources.0.title', 'publicgroup');
        $this->assertStringNotContainsString('session_name', $response->getContent());
        $task = TelegramTracking::firstOrFail();
        $this->assertTrue($task->expires_at->equalTo(now()->addMonthNoOverflow()));
        $this->assertTrue($task->sources()->first()->next_check_at->equalTo(now()->addHours(6)));
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    public static function invalidInputs(): array
    {
        return [
            [['query' => 'аб'], 'query'], [['query' => '   '], 'query'],
            [['mode' => 'user', 'query' => '@someone'], 'query'], [['mode' => 'user', 'query' => '0'], 'query'],
            [['groups' => ['aaaaa', 'bbbbb', 'ccccc', 'ddddd']], 'groups'],
            [['groups' => ['https://evil.test/group']], 'groups.0'],
            [['groups' => ['@samegroup', 'https://t.me/samegroup']], 'groups.0'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_input(array $overrides, string $field): void
    {
        $this->actingAs($this->user)->postJson('/telegram/tracking', $this->input($overrides))->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('telegram_trackings', 0);
    }

    public function test_unavailable_group_never_creates_partial_task(): void
    {
        $this->gateway->failure = new TrackingException('group_unavailable');
        $this->actingAs($this->user)->postJson('/telegram/tracking', $this->input())->assertUnprocessable()->assertJsonPath('code', 'group_unavailable');
        $this->assertDatabaseCount('telegram_trackings', 0);
    }

    public static function plans(): array
    {
        return [['free', 1], ['plus', 3], ['pro', 5]];
    }

    #[DataProvider('plans')]
    public function test_plan_limits_are_enforced_on_create_and_resume(string $plan, int $limit): void
    {
        $this->subscribe($plan);
        for ($i = 0; $i < $limit; $i++) {
            $this->create();
        }
        $this->actingAs($this->user)->postJson('/telegram/tracking', $this->input())->assertUnprocessable()->assertJsonPath('code', 'limit');
        $first = TelegramTracking::first();
        $this->change($first, 'pause')->assertOk();
        $this->change($first, 'resume')->assertOk();
        $this->assertSame($limit, TelegramTracking::where('status', 'active')->count());
    }

    public function test_quota_is_rechecked_after_remote_validation(): void
    {
        $this->gateway->onResolve = function (): void {
            $this->gateway->onResolve = null;
            $this->create();
        };
        $this->actingAs($this->user)->postJson('/telegram/tracking', $this->input())->assertUnprocessable()->assertJsonPath('code', 'limit');
        $this->assertDatabaseCount('telegram_trackings', 1);
    }

    public function test_foreign_user_cannot_read_mutate_or_export(): void
    {
        $task = $this->create();
        $this->actingAs(User::factory()->create());
        $this->getJson('/telegram/tracking')->assertJsonCount(0, 'items');
        $this->getJson('/telegram/tracking/'.$task->id.'/messages')->assertNotFound();
        $this->getJson('/telegram/tracking/'.$task->id.'/export/json')->assertNotFound();
        $this->getJson('/telegram/tracking/'.$task->id.'/export/xlsx')->assertNotFound();
        $this->patchJson('/telegram/tracking/'.$task->id, ['action' => 'stop'])->assertNotFound();
        $this->assertSame('active', $task->fresh()->status);
    }

    public function test_guest_unverified_and_blocked_access_is_denied(): void
    {
        $this->getJson('/telegram/tracking')->assertUnauthorized();
        $this->actingAs(User::factory()->unverified()->create())->getJson('/telegram/tracking')->assertForbidden();
        $this->actingAs(User::factory()->create(['is_blocked' => true]))->getJson('/telegram/tracking')->assertRedirect();
    }

    public function test_expired_subscription_pauses_all_paid_tasks_and_requires_manual_resume(): void
    {
        $this->subscribe('plus', 1);
        $first = $this->create();
        $second = $this->create();
        $this->travel(2)->days();
        app(TrackingScheduler::class)->maintain();
        $this->assertSame(2, TelegramTracking::where('pause_reason', 'subscription_expired')->count());
        $this->assertSame(2, $this->user->notifications()->count());
        app(TrackingScheduler::class)->maintain();
        $this->assertSame(2, $this->user->notifications()->count());
        $this->change($first, 'resume')->assertOk();
        $this->change($second, 'resume')->assertUnprocessable()->assertJsonPath('code', 'limit');
    }

    public function test_continuous_subscription_extension_does_not_pause(): void
    {
        $this->subscribe('plus', 1);
        $task = $this->create();
        $this->user->subscriptions()->update(['ends_at' => now()->addDays(10)]);
        $this->travel(2)->days();
        app(TrackingLifecycle::class)->synchronize($this->user->id);
        $this->assertSame('active', $task->fresh()->status);
    }

    public function test_stop_invalidates_queued_job_and_retains_for_seven_days(): void
    {
        $task = $this->create();
        $source = $task->sources()->first();
        $source->update(['lease_token' => 'old-token']);
        $this->change($task, 'stop')->assertOk();
        app(TrackingCollector::class)->collect($source->id, 'old-token');
        $this->assertSame([], $this->gateway->requests);
        $this->assertTrue($task->fresh()->purge_at->equalTo(now()->addDays(7)));
        $this->change($task, 'resume')->assertUnprocessable();
        $this->travel(7)->days();
        $this->actingAs($this->user)->getJson('/telegram/tracking/'.$task->id.'/export/json')->assertNotFound();
        app(TrackingScheduler::class)->maintain();
        $this->assertDatabaseCount('telegram_trackings', 0);
        $this->assertDatabaseCount('telegram_tracking_sources', 0);
    }

    public function test_renewal_does_not_discard_messages_and_is_bounded(): void
    {
        $task = $this->create();
        $this->change($task, 'renew')->assertUnprocessable();
        $old = $task->expires_at;
        $this->travelTo($old->subDays(3));
        $this->change($task, 'renew')->assertOk();
        $this->assertTrue($task->fresh()->expires_at->equalTo($old->addMonthNoOverflow()));
        $this->change($task, 'renew')->assertUnprocessable();
    }

    public function test_scheduler_recovers_expired_leases_but_does_not_double_dispatch(): void
    {
        $task = $this->create();
        $this->travel(6)->hours();
        $scheduler = app(TrackingScheduler::class);
        $this->assertSame(1, $scheduler->dispatch());
        $this->assertSame(0, $scheduler->dispatch());
        $this->travel(6)->minutes();
        $this->assertSame(1, $scheduler->dispatch());
        Bus::assertDispatchedTimes(CollectTrackingSource::class, 2);
        $this->assertNotNull($task->sources()->first()->lease_token);
    }

    public function test_paginated_collection_is_idempotent_and_notifies_once_on_completion(): void
    {
        config(['telegram_tracking.page_size' => 2]);
        $task = $this->create();
        $source = $task->sources()->first();
        $this->travel(6)->hours();
        $this->gateway->pages = [[$this->message(3), $this->message(2)], [$this->message(1)]];
        $this->collect($source);
        $this->assertDatabaseCount('telegram_tracking_messages', 2);
        $this->assertSame(0, $source->fresh()->cursor_id);
        $this->assertSame(2, $source->fresh()->offset_id);
        $this->assertSame(0, $this->user->notifications()->count());
        $this->collect($source);
        $this->assertSame(3, $source->fresh()->cursor_id);
        $this->assertNull($source->fresh()->window_end);
        $this->assertSame(1, $this->user->notifications()->count());
        $this->assertSame(3, $this->user->notifications()->first()->data['body_params']['count']);
        $this->gateway->pages = [[$this->message(3)]];
        $this->collect($source);
        $this->assertDatabaseCount('telegram_tracking_messages', 3);
        $this->assertSame(1, $this->user->notifications()->count());
    }

    public function test_pause_during_network_call_prevents_late_writes(): void
    {
        $task = $this->create();
        $source = $task->sources()->first();
        $this->travel(6)->hours();
        $this->gateway->pages = [[$this->message(1)]];
        $this->gateway->onHistory = fn () => app(TrackingService::class)->change($this->user, $task->id, 'pause');
        $this->collect($source);
        $this->assertDatabaseCount('telegram_tracking_messages', 0);
        $this->assertSame('paused', $task->fresh()->status);
    }

    public function test_reclaimed_lease_rejects_old_job_before_requesting_telegram(): void
    {
        $source = $this->create()->sources()->first();
        $this->travel(6)->hours();
        app(TrackingScheduler::class)->dispatch();
        $oldToken = $source->fresh()->lease_token;
        $this->travel(6)->minutes();
        app(TrackingScheduler::class)->dispatch();
        $newToken = $source->fresh()->lease_token;

        $this->assertNotSame($oldToken, $newToken);
        app(TrackingCollector::class)->collect($source->id, $oldToken);
        $this->assertSame([], $this->gateway->requests);
        $this->assertSame($newToken, $source->fresh()->lease_token);
        $this->assertDatabaseCount('telegram_tracking_messages', 0);
    }

    public function test_stalled_page_does_not_persist_partial_matches_and_can_be_retried(): void
    {
        config(['telegram_tracking.page_size' => 2]);
        $source = $this->create()->sources()->first();
        $this->travel(6)->hours();
        $source->update(['offset_id' => 7, 'high_id' => 10, 'window_end' => now()]);
        $this->gateway->pages = [[$this->message(8), $this->message(7)]];
        $this->collect($source);

        $this->assertDatabaseCount('telegram_tracking_messages', 0);
        $this->assertSame(7, $source->fresh()->offset_id);
        $this->assertSame(10, $source->fresh()->high_id);
        $this->assertSame('pagination_stalled', $source->fresh()->error_code);
        $this->assertSame(0, $this->user->notifications()->count());

        $this->gateway->pages = [[$this->message(6)]];
        $this->collect($source);
        $this->assertDatabaseCount('telegram_tracking_messages', 1);
        $this->assertSame(10, $source->fresh()->cursor_id);
        $this->assertNull($source->fresh()->error_code);
        $this->assertSame(1, $this->user->notifications()->count());
    }

    public function test_renewal_availability_matches_the_action_at_window_boundary(): void
    {
        $task = $this->create();
        $window = app(TrackingConfig::class)->integer('renewal_window_days');
        $this->travelTo($task->expires_at->subDays($window)->subSecond());
        $this->actingAs($this->user)->getJson('/telegram/tracking')->assertJsonPath('items.0.can_renew', false);
        $this->change($task, 'renew')->assertUnprocessable()->assertJsonPath('code', 'renew_too_early');

        $this->travel(1)->seconds();
        $this->getJson('/telegram/tracking')->assertJsonPath('items.0.can_renew', true);
        $this->change($task, 'renew')->assertOk();
        $this->getJson('/telegram/tracking')->assertJsonPath('items.0.can_renew', false);
    }

    public function test_flood_wait_preserves_checkpoint_and_never_shortens_telegram_deadline(): void
    {
        $task = $this->create();
        $source = $task->sources()->first();
        $source->update(['offset_id' => 123, 'window_end' => now()]);
        $this->gateway->failure = new TrackingException('flood_wait', 100000);
        $this->collect($source);
        $source->refresh();
        $this->assertSame(123, $source->offset_id);
        $this->assertSame('flood_wait', $source->error_code);
        $this->assertTrue($source->next_check_at->equalTo(now()->addSeconds(100000)));
        $this->assertNull($source->lease_token);
    }

    public function test_keyword_and_sender_matching_does_not_match_mentions_or_forwarded_author(): void
    {
        $matcher = new TrackingMatcher;
        $task = new TelegramTracking(['mode' => 'keyword', 'query' => 'тест']);
        $message = $this->message(1, 'Это ТЕСТ сообщения');
        $this->assertTrue($matcher->matches($task, $message));
        $task->mode = 'user';
        $task->query = '99';
        $message['message'] = '99';
        $message['fwd_from'] = ['from_id' => ['_' => 'peerUser', 'user_id' => 99]];
        $this->assertFalse($matcher->matches($task, $message));
        $message['from_id'] = ['_' => 'peerUser', 'user_id' => 99];
        $this->assertTrue($matcher->matches($task, $message));
        $message['from_id'] = ['_' => 'peerChannel', 'channel_id' => 99];
        $this->assertFalse($matcher->matches($task, $message));
    }

    public function test_reports_keep_messages_older_than_seven_days_and_use_safe_localized_excel(): void
    {
        $task = $this->create();
        $source = $task->sources()->first();
        $this->travel(6)->hours();
        $this->gateway->pages = [[$this->message(1, '=HYPERLINK("https://example.test","test")')]];
        $this->collect($source);
        $this->travel(10)->days();
        app(TrackingScheduler::class)->maintain();
        $json = $this->actingAs($this->user)->get('/telegram/tracking/'.$task->id.'/export/json')->assertOk()->streamedContent();
        $this->assertCount(1, json_decode($json, true, flags: JSON_THROW_ON_ERROR)['messages']);
        Storage::fake('local');
        foreach (['en' => 'Message', 'ru' => 'Сообщение'] as $locale => $heading) {
            app()->setLocale($locale);
            Excel::store(app(TrackingReports::class)->workbook($task), 'tracking.xlsx', 'local', Writer::XLSX);
            $book = IOFactory::load(Storage::disk('local')->path('tracking.xlsx'));
            $sheet = $book->getSheet(1);
            $this->assertSame($heading, $sheet->getCell('E1')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('E2')->getDataType());
            $this->assertSame('A2', $sheet->getFreezePane());
            $this->assertSame('https://t.me/publicgroup/1', $sheet->getCell('H2')->getHyperlink()->getUrl());
            $book->disconnectWorksheets();
        }
    }

    public function test_expired_tasks_are_not_dispatched_and_are_pruned_with_messages(): void
    {
        $task = $this->create();
        $this->travelTo($task->expires_at);
        app(TrackingScheduler::class)->maintain();
        $this->assertSame('expired', $task->fresh()->status);
        $this->assertSame(0, app(TrackingScheduler::class)->dispatch());
        $this->travel(7)->days();
        app(TrackingScheduler::class)->maintain();
        $this->assertDatabaseCount('telegram_trackings', 0);
        $this->assertDatabaseCount('telegram_tracking_messages', 0);
    }

    public function test_dynamic_interval_is_bounded_and_sync_queue_is_rejected(): void
    {
        $config = app(TrackingConfig::class);
        $this->assertSame(6, $config->interval(1));
        $this->assertSame(12, $config->interval(31));
        $this->assertSame(24, $config->interval(10000));
        config(['telegram_tracking.queue.connection' => 'sync']);
        $this->actingAs($this->user)->postJson('/telegram/tracking', $this->input())->assertUnprocessable()->assertJsonPath('code', 'queue_unavailable');
    }

    private function input(array $overrides = []): array
    {
        return [...['name' => 'Test tracking', 'mode' => 'keyword', 'query' => 'test', 'groups' => ['publicgroup'], 'notify_bot' => false], ...$overrides];
    }

    private function create(): TelegramTracking
    {
        return app(TrackingService::class)->create($this->user, $this->input());
    }

    private function subscribe(string $plan, int $days = 30): void
    {
        if ($plan !== 'free') {
            $this->user->subscriptions()->create(['plan' => $plan, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays($days)]);
        }
    }

    private function change(TelegramTracking $task, string $action)
    {
        return $this->actingAs($this->user)->patchJson('/telegram/tracking/'.$task->id, ['action' => $action]);
    }

    private function collect(TelegramTrackingSource $source): void
    {
        $token = (string) Str::uuid();
        $source->update(['lease_token' => $token, 'lease_until' => now()->addMinutes(5)]);
        app(TrackingCollector::class)->collect($source->id, $token);
    }

    private function message(int $id, string $text = 'test message'): array
    {
        return ['_' => 'message', 'id' => $id, 'date' => now()->subMinute()->timestamp, 'message' => $text, 'from_id' => ['_' => 'peerUser', 'user_id' => 42]];
    }
}
