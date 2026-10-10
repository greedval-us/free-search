<?php

namespace Tests\Feature;

use App\Exceptions\PublicException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportScheduleContractTest extends TestCase
{
    use RefreshDatabase;

    public static function modules(): array
    {
        return [
            'telegram' => ['Telegram\\Analytics\\Reports\\AnalyticsReportScheduleService', 'TelegramAnalyticsSchedule', 'telegram_analytics_reports', ['groups' => ['@example']]],
            'youtube' => ['YouTube\\Analytics\\Reports\\AnalyticsReportScheduleService', 'YouTubeAnalyticsSchedule', 'youtube_analytics_reports', ['channels' => ['@example']]],
            'bluesky' => ['Bluesky\\Analytics\\Reports\\AnalyticsReportScheduleService', 'BlueskyAnalyticsSchedule', 'bluesky_analytics_reports', ['accounts' => ['example.bsky.social']]],
            'mastodon' => ['Mastodon\\Analytics\\Reports\\AnalyticsReportScheduleService', 'MastodonAnalyticsSchedule', 'mastodon_analytics_reports', ['accounts' => ['@example@mastodon.social']]],
            'site-intel' => ['SiteIntel\\Application\\Reports\\ReportScheduleService', 'SiteIntelReportSchedule', 'site_intel_reports', ['targets' => ['example.com'], 'report_type' => 'analytics']],
            'news' => ['NewsMediaIntel\\Application\\Reports\\ReportScheduleService', 'NewsMediaReportSchedule', 'news_media_reports', ['queries' => ['example']]],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 12:00:00', 'UTC'));
        config()->set('access.plans.free', array_fill_keys(array_keys(config('access.plans.free')), 100));
        config()->set('queue.connections.database.retry_after', 1200);
    }

    #[DataProvider('modules')]
    public function test_pause_resume_and_delete_preserve_owner_and_repeated_resume_date(string $serviceName, string $modelName, string $config, array $targets): void
    {
        config()->set($config.'.queue.connection', 'database');
        $user = User::factory()->create();
        $service = app('App\\Modules\\'.$serviceName);
        $schedule = $service->create($user, $targets + ['name' => 'Contract', 'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Berlin']);

        $service->change($user, $schedule->id, 'pause');
        $service->change($user, $schedule->id, 'pause');
        $this->assertFalse($schedule->fresh()->enabled);
        $this->travel(2)->days();
        $service->change($user, $schedule->id, 'resume');
        $resumedAt = $schedule->fresh()->next_run_at;
        $this->travel(1)->days();
        $service->change($user, $schedule->id, 'resume');

        $this->assertTrue($schedule->fresh()->enabled);
        $this->assertSame($resumedAt->toIso8601String(), $schedule->fresh()->next_run_at->toIso8601String());
        $this->assertSame('09:00', $resumedAt->setTimezone('Europe/Berlin')->format('H:i'));
        $service->destroy($user, $schedule->id);
        $this->assertSoftDeleted($schedule);
    }

    #[DataProvider('modules')]
    public function test_repeated_manual_run_creates_one_occurrence_and_keeps_a_paused_schedules_cadence(string $serviceName, string $modelName, string $config, array $targets): void
    {
        config()->set($config.'.queue.connection', 'database');
        $user = User::factory()->create();
        $service = app('App\\Modules\\'.$serviceName);
        $schedule = $service->create($user, $targets + ['name' => 'Manual', 'interval' => '1', 'send_time' => '09:00', 'timezone' => 'UTC']);
        $service->change($user, $schedule->id, 'pause');
        $nextRun = $schedule->fresh()->next_run_at->toIso8601String();
        Queue::fake();

        $service->runNow($user, $schedule->id);
        $service->runNow($user, $schedule->id);

        $this->assertSame(1, $schedule->reports()->count());
        $this->assertTrue($schedule->reports()->sole()->is_manual);
        $this->assertFalse($schedule->fresh()->enabled);
        $this->assertSame($nextRun, $schedule->fresh()->next_run_at->toIso8601String());
        Queue::assertCount(1);
    }

    #[DataProvider('modules')]
    public function test_foreign_schedules_cannot_be_changed_or_deleted(string $serviceName, string $modelName, string $config, array $targets): void
    {
        config()->set($config.'.queue.connection', 'database');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $service = app('App\\Modules\\'.$serviceName);
        $schedule = $service->create($owner, $targets + ['name' => 'Private', 'interval' => '1', 'send_time' => '09:00', 'timezone' => 'UTC']);

        foreach (['pause', 'resume', 'destroy', 'runNow'] as $action) {
            try {
                in_array($action, ['pause', 'resume'], true)
                    ? $service->change($other, $schedule->id, $action)
                    : $service->{$action}($other, $schedule->id);
                $this->fail('Foreign schedule was accessible through '.$action);
            } catch (ModelNotFoundException) {
                $this->assertTrue($schedule->fresh()->enabled);
                $this->assertNull($schedule->fresh()->deleted_at);
            }
        }
    }

    #[DataProvider('modules')]
    public function test_creation_at_limit_is_atomic_and_validation_keeps_public_reasons(string $serviceName, string $modelName, string $config, array $targets): void
    {
        config()->set($config.'.queue.connection', 'database');
        config()->set($config.'.max_schedules', 1);
        $user = User::factory()->create();
        $service = app('App\\Modules\\'.$serviceName);
        $data = $targets + ['name' => 'Contract', 'interval' => '1', 'send_time' => '09:00', 'timezone' => 'UTC'];
        $service->create($user, $data);

        foreach ([['interval', '2', 'invalid_interval'], ['send_time', '24:00', 'invalid_time'], ['timezone', 'invalid/zone', 'invalid_timezone'], ['name', 'Second', 'schedule_limit']] as [$field, $value, $reason]) {
            try {
                $service->create($user, array_replace($data, [$field => $value]));
                $this->fail('Invalid schedule accepted: '.$reason);
            } catch (PublicException $exception) {
                $this->assertSame($reason, $exception->reason);
            }
        }

        $model = 'App\\Models\\'.$modelName;
        $this->assertSame(1, $model::query()->forUser($user->id)->count());
    }
}
