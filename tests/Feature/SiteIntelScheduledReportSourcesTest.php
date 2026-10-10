<?php

namespace Tests\Feature;

use App\Models\SiteIntelReportSchedule;
use App\Models\SiteIntelScheduledReport;
use App\Models\User;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelAnalyticsServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelHostResolverInterface;
use App\Modules\SiteIntel\Application\Reports\Events\SiteIntelReportCompleted;
use App\Modules\SiteIntel\Application\Reports\Jobs\GenerateSiteIntelReport;
use App\Modules\SiteIntel\Application\Reports\PublicSiteTarget;
use App\Modules\SiteIntel\Application\Reports\ReportException;
use App\Modules\SiteIntel\Application\Reports\ReportGenerator;
use App\Modules\SiteIntel\Application\Reports\ReportScheduleService;
use App\Modules\SiteIntel\Application\Reports\ReportSourceBuilder;
use App\Modules\SiteIntel\DTO\Result\SeoAuditResultDTO;
use App\Modules\SiteIntel\DTO\Result\SiteIntelAnalyticsResultDTO;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteIntelScheduledReportSourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00', 'UTC'));
        config()->set('site_intel_reports.queue.connection', 'site-intel-reports-database');
        config()->set('queue.connections.site-intel-reports-database', [
            ...config('queue.connections.database'), 'retry_after' => 960,
        ]);
        config()->set('access.plans.free', [...config('access.plans.free'),
            'site-intel.analytics' => 100, 'site-intel.seo-audit' => 100]);
        Http::preventStrayRequests();
    }

    public static function siteTargets(): array
    {
        return [
            'bare domain' => ['Example.org', 'https://example.org/'],
            'http' => ['http://Example.org', 'http://example.org/'],
            'https URL' => ['https://Example.org/Some/Path?q=X', 'https://example.org/Some/Path?q=X'],
            'fragment' => ['https://example.org/path#section', 'https://example.org/path'],
            'surrounding spaces' => [' example.org ', 'https://example.org/'],
            'domain with digits' => ['shop2.example.org', 'https://shop2.example.org/'],
            'IP' => ['127.0.0.1', null],
            'public IP' => ['8.8.8.8', null],
            'IPv6' => ['https://[2606:4700:4700::1111]/', null],
            'integer host' => ['2130706433', null],
            'hex host' => ['0x7f000001', null],
            'octal host' => ['0177.0.0.1', null],
            'localhost' => ['localhost', null],
            'localhost subdomain' => ['http://safe.localhost', null],
            'private suffix' => ['http://host.local', null],
            'internal suffix' => ['http://host.internal', null],
            'test suffix' => ['http://host.test', null],
            'credentials' => ['https://admin:secret@example.org', null],
            'username' => ['https://admin@example.org', null],
            'port' => ['https://example.org:8443/', null],
            'explicit default port' => ['https://example.org:443/', null],
            'trailing hostname dot' => ['https://example.org./', null],
            'leading hostname dot' => ['.example.org', null],
            'multiple leading hostname dots' => ['https://..example.org/', null],
            'wrong scheme' => ['ftp://example.org/', null],
            'javascript' => ['javascript:alert(1)', null],
            'scheme relative' => ['//example.org/', null],
            'backslash' => ['https://example.org\\@localhost/', null],
            'embedded newline' => ["https://example.org/\nsecret", null],
            'embedded space' => ['https://example.org/a b', null],
            'long URL' => ['https://example.org/'.str_repeat('a', 512), null],
            'array' => [[], null],
            'number' => [123, null],
        ];
    }

    #[DataProvider('siteTargets')]
    public function test_only_public_http_hostname_syntax_is_accepted_without_network_lookup(mixed $input, ?string $expected): void
    {
        $this->mock(SiteIntelHostResolverInterface::class)->shouldNotReceive('resolve');

        $this->assertSame($expected, PublicSiteTarget::normalize($input));
    }

    public function test_analytics_delegates_existing_engine_and_keeps_result_payload_plus_snapshot_metadata(): void
    {
        $report = $this->report('analytics');
        $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->once()
            ->with('example.org')->andReturn(['93.184.216.34']);
        $data = ['checkedAt' => '2026-10-09T12:00:00+00:00', 'siteHealth' => ['status' => 200], 'overview' => ['score' => 90]];
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldReceive('analyze')->once()
            ->with('https://example.org/Path', 'example.org')->andReturn(new SiteIntelAnalyticsResultDTO($data));
        $this->mock(SeoAuditServiceInterface::class)->shouldNotReceive('audit');

        $result = app(ReportSourceBuilder::class)->build($report);

        $this->assertSame($data['siteHealth'], $result['siteHealth']);
        $this->assertSame($data['overview'], $result['overview']);
        $this->assertSame([
            'type' => 'analytics', 'targetUrl' => 'https://example.org/Path',
            'scheduledFor' => '2026-10-09T11:00:00+00:00', 'checkedAt' => $data['checkedAt'],
            'timezone' => 'Europe/Moscow', 'frequency' => '7', 'metricsBasis' => 'snapshot_at_check_time',
        ], $result['reportSchedule']);
        $this->assertArrayNotHasKey('range', $result);
    }

    public function test_seo_auto_profile_passes_null_and_preserves_existing_engine_result(): void
    {
        $report = $this->report('seo-audit');
        $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->once()->andReturn(['93.184.216.34']);
        $this->mock(SeoAuditServiceInterface::class)->shouldReceive('audit')->once()
            ->with('https://example.org/Path', 8, null)->andReturn(new SeoAuditResultDTO([
                'checkedAt' => '2026-10-09T12:00:00+00:00', 'crawl' => ['pages' => 8], 'score' => ['value' => 70],
            ]));
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldNotReceive('analyze');

        $result = app(ReportSourceBuilder::class)->build($report);

        $this->assertSame(['pages' => 8], $result['crawl']);
        $this->assertSame(['value' => 70], $result['score']);
        $this->assertSame('seo-audit', $result['reportSchedule']['type']);
    }

    public static function unsafeDns(): array
    {
        return [
            'missing host' => [[]],
            'IPv4 loopback' => [['127.0.0.1']],
            'IPv6 loopback' => [['::1']],
            'private IPv4' => [['192.168.1.10']],
            'link local metadata' => [['169.254.169.254']],
            'mixed records' => [['93.184.216.34', '10.0.0.1']],
        ];
    }

    #[DataProvider('unsafeDns')]
    public function test_execution_revalidates_dns_and_rejects_changed_or_mixed_private_records_without_external_calls(array $addresses): void
    {
        $user = User::factory()->create();
        // Creating a schedule is syntax-only and must not make DNS/network requests.
        $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->once()->andReturn($addresses);
        $schedule = app(ReportScheduleService::class)->create($user, [
            'name' => 'Site', 'targets' => ['example.org'], 'report_type' => 'analytics',
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow',
        ]);
        Bus::fake([GenerateSiteIntelReport::class]);
        Event::fake([SiteIntelReportCompleted::class]);
        app(ReportScheduleService::class)->runNow($user, $schedule->id);
        $report = SiteIntelScheduledReport::query()->firstOrFail();
        $this->mock(SiteIntelAnalyticsServiceInterface::class)->shouldNotReceive('analyze');
        $this->mock(SeoAuditServiceInterface::class)->shouldNotReceive('audit');

        app(ReportGenerator::class)->generate($report->id, $report->lease_token);

        $this->assertSame(SiteIntelScheduledReport::FAILED, $report->fresh()->status);
        $this->assertSame('invalid_target', $report->fresh()->error_code);
        $this->assertFalse($report->fresh()->quota_charged);
        $this->assertNull($report->fresh()->data);
        Event::assertNotDispatched(SiteIntelReportCompleted::class);
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 1);
    }

    public static function invalidScheduleData(): array
    {
        return [
            'bad type' => [['report_type' => 'history'], 'invalid_type'],
            'low crawl limit' => [['crawl_limit' => 2], 'invalid_options'],
            'high crawl limit' => [['crawl_limit' => 21], 'invalid_options'],
            'fractional crawl limit' => [['crawl_limit' => 8.5], 'invalid_options'],
            'wrong platform' => [['platform_type' => 'other'], 'invalid_options'],
            'invalid target' => [['targets' => ['localhost']], 'invalid_targets'],
            'empty targets' => [['targets' => []], 'invalid_targets'],
            'too many targets' => [['targets' => ['a.example.org', 'b.example.org', 'c.example.org', 'd.example.org']], 'invalid_targets'],
            'wrong interval' => [['interval' => '2'], 'invalid_interval'],
            'wrong time' => [['send_time' => '25:00'], 'invalid_time'],
            'wrong timezone' => [['timezone' => 'Moscow'], 'invalid_timezone'],
        ];
    }

    #[DataProvider('invalidScheduleData')]
    public function test_core_rejects_invalid_schedule_inputs_before_persistence(array $override, string $reason): void
    {
        try {
            app(ReportScheduleService::class)->create(User::factory()->create(), [...[
                'name' => 'Site', 'targets' => ['example.org'], 'report_type' => 'analytics',
                'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow',
            ], ...$override]);
            $this->fail('Invalid schedule input must be rejected.');
        } catch (ReportException $exception) {
            $this->assertSame($reason, $exception->reason);
        }

        $this->assertDatabaseCount('site_intel_report_schedules', 0);
    }

    public function test_case_sensitive_url_paths_remain_distinct_occurrences(): void
    {
        $user = User::factory()->create();
        $schedule = app(ReportScheduleService::class)->create($user, [
            'name' => 'Site', 'targets' => ['https://example.org/Path', 'https://example.org/path'], 'report_type' => 'analytics',
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow',
        ]);
        Bus::fake([GenerateSiteIntelReport::class]);

        app(ReportScheduleService::class)->runNow($user, $schedule->id);

        $this->assertSame(['https://example.org/Path', 'https://example.org/path'], SiteIntelScheduledReport::query()->orderBy('id')->pluck('target_url')->all());
        Bus::assertDispatchedTimes(GenerateSiteIntelReport::class, 2);
    }

    private function report(string $type): SiteIntelScheduledReport
    {
        $schedule = new SiteIntelReportSchedule(['timezone' => 'Europe/Moscow', 'interval' => '7']);

        return (new SiteIntelScheduledReport([
            'target_url' => 'https://example.org/Path', 'report_type' => $type, 'crawl_limit' => 8,
            'platform_type' => 'auto', 'scheduled_for' => CarbonImmutable::parse('2026-10-09 11:00:00', 'UTC'),
        ]))->setRelation('schedule', $schedule);
    }
}
