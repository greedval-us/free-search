<?php

namespace Tests\Feature;

use App\Exceptions\PublicException;
use App\Modules\Bluesky\Analytics\Reports\AnalyticsReportConfig as BlueskyConfig;
use App\Modules\Mastodon\Analytics\Reports\AnalyticsReportConfig as MastodonConfig;
use App\Modules\NewsMediaIntel\Application\Reports\ReportConfig as NewsConfig;
use App\Modules\SiteIntel\Application\Reports\ReportConfig as SiteConfig;
use App\Modules\Telegram\Analytics\Reports\AnalyticsReportConfig as TelegramConfig;
use App\Modules\YouTube\Analytics\Reports\AnalyticsReportConfig as YouTubeConfig;
use App\Support\Reports\ReportQueueSafety;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ReportQueueSafetyTest extends TestCase
{
    public static function modules(): array
    {
        return [
            [TelegramConfig::class, 'telegram_analytics_reports'], [YouTubeConfig::class, 'youtube_analytics_reports'],
            [BlueskyConfig::class, 'bluesky_analytics_reports'], [MastodonConfig::class, 'mastodon_analytics_reports'],
            [SiteConfig::class, 'site_intel_reports'], [NewsConfig::class, 'news_media_reports'],
        ];
    }

    #[DataProvider('modules')]
    public function test_each_module_uses_shared_queue_policy_and_retains_its_public_error(string $class, string $key): void
    {
        config([$key.'.queue.connection' => 'report-test', $key.'.queue.timeout' => 120, $key.'.lease_seconds' => 300,
            'queue.connections.report-test' => ['driver' => 'database', 'retry_after' => 180]]);
        $reports = app($class);
        $reports->ensureQueue();
        config(['queue.connections.report-test.retry_after' => 120]);

        try {
            $reports->ensureQueue();
            $this->fail('A reservation must outlive the worker timeout.');
        } catch (PublicException $exception) {
            $this->assertSame('queue_unavailable', $exception->errorCode());
        }
    }

    public static function configurations(): array
    {
        return [
            'database' => ['database', 180, 300, 'testing', 'database', true],
            'redis' => ['redis', 180, 300, 'production', 'redis', true],
            'sync' => ['sync', 180, 300, 'testing', 'database', false],
            'missing driver' => [null, 180, 300, 'testing', 'database', false],
            'missing reservation' => ['database', null, 300, 'testing', 'database', false],
            'nonnumeric reservation' => ['database', 'unknown', 300, 'testing', 'database', false],
            'reservation equals timeout' => ['database', 120, 300, 'testing', 'database', false],
            'reservation shorter than timeout' => ['database', 119, 300, 'testing', 'database', false],
            'lease equals timeout' => ['database', 180, 120, 'testing', 'database', false],
            'lease shorter than timeout' => ['database', 180, 119, 'testing', 'database', false],
            'temporary cache in production' => ['database', 180, 300, 'production', 'array', false],
            'temporary cache in tests' => ['database', 180, 300, 'testing', 'array', true],
        ];
    }

    #[DataProvider('configurations')]
    public function test_durable_execution_requires_safe_reservation_lease_and_cache(?string $driver, mixed $retryAfter, int $lease, string $environment, string $cache, bool $expected): void
    {
        $this->app->instance('env', $environment);
        config(['queue.connections.report-test' => ['driver' => $driver, 'retry_after' => $retryAfter],
            'cache.default' => 'report-test', 'cache.stores.report-test.driver' => $cache]);

        $this->assertSame($expected, app(ReportQueueSafety::class)->supports('report-test', 120, $lease));
    }
}
