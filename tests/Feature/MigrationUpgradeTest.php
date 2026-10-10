<?php

namespace Tests\Feature;

use App\Jobs\ProcessParserRun;
use App\Models\ParserRun;
use App\Models\User;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\ParserSupport\ParserRunSourceRequestBudget;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelAnalyticsServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelHostResolverInterface;
use App\Modules\SiteIntel\Application\Reports\Jobs\GenerateSiteIntelReport;
use App\Modules\SiteIntel\Application\Reports\ReportGenerator;
use App\Modules\YouTube\Parser\YouTubeParserRunStore;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Feature\Concerns\CreatesSubscribedUser;
use Tests\Support\MigrationUpgradeDatabase;
use Tests\TestCase;

class MigrationUpgradeTest extends TestCase
{
    use CreatesSubscribedUser;

    private array $databaseConfiguration;

    private const TOKEN = '22222222-2222-4222-8222-222222222222';

    /** @return array<string, array{inputs: string, target: string, job: class-string, generator: class-string, source: class-string, method: string}> */
    private static function modules(): array
    {
        $modules = [];
        foreach (['Bluesky' => ['accounts', 'account_input'], 'Mastodon' => ['accounts', 'account_input'], 'YouTube' => ['channels', 'channel_input'], 'Telegram' => ['groups', 'chat_username']] as $module => [$inputs, $target]) {
            $root = 'App\\Modules\\'.$module;
            $modules[strtolower($module)] = [
                'inputs' => $inputs, 'target' => $target,
                'job' => $root.'\\Analytics\\Reports\\Jobs\\GenerateAnalyticsReport',
                'generator' => $root.'\\Analytics\\Reports\\AnalyticsReportGenerator',
                'source' => $module === 'Telegram' ? $root.'\\Analytics\\Contracts\\TelegramAnalyticsApplicationServiceInterface' : $root.'\\Analytics\\Reports\\Scheduled'.$module.'Analytics',
                'method' => $module === 'Telegram' ? 'buildSummary' : 'build',
            ];
        }
        $modules['site_intel'] = [
            'inputs' => 'targets', 'target' => 'target_url',
            'job' => GenerateSiteIntelReport::class,
            'generator' => ReportGenerator::class,
            'source' => SiteIntelAnalyticsServiceInterface::class,
            'method' => 'analyze',
        ];

        return $modules;
    }

    protected function setUp(): void
    {
        $configuration = MigrationUpgradeDatabase::configuration();
        if ($configuration === null) {
            $this->markTestSkipped('Requires an explicitly configured empty dedicated MySQL/MariaDB migration-upgrade database.');
        }
        $this->databaseConfiguration = $configuration;
        parent::setUp();
    }

    public function createApplication()
    {
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->traitsUsedByTest = class_uses_recursive(static::class);
        $app->loadEnvironmentFrom('.env.migration-upgrade-does-not-exist');
        if ($app->configurationIsCached()) {
            throw new RuntimeException('Migration upgrade tests refuse cached application configuration.');
        }
        $app->afterBootstrapping(LoadConfiguration::class, function ($app): void {
            $app['config']->set('database.default', 'refactor_upgrade');
            $app['config']->set('database.connections.refactor_upgrade', $this->databaseConfiguration);
            $app['config']->set('app.timezone', 'UTC');
            $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('u', 32)));
            $app['config']->set('cache.default', 'array');
            $app['config']->set('queue.default', 'database');
            $app['config']->set('queue.connections.database.connection', 'refactor_upgrade');
            $app['config']->set('queue.connections.database.retry_after', 1200);
            $app['config']->set('osint.parser_runs.queue.enabled', false);
            foreach (array_keys(self::modules()) as $module) {
                $app['config']->set($module.($module === 'site_intel' ? '_reports' : '_analytics_reports').'.queue.connection', 'database');
            }
        });
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function test_the_real_baseline_schema_upgrades_without_losing_legacy_reports_jobs_or_parser_state(): void
    {
        $this->assertSame([], DB::select('SHOW TABLES'), 'Use a newly created dedicated database. This test never wipes or rolls back an existing schema.');
        Http::preventStrayRequests();
        Storage::fake('private');
        $this->travelTo(CarbonImmutable::parse('2026-10-10 12:00:00 UTC'));
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/refactor-upgrade-baseline.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('415d288ed13f7b8dee9b96dc98c798e3cee16116', $fixture['baseline']);
        foreach ($fixture['migrations'] as $path => $hash) {
            $this->assertSame($hash, hash('sha256', str_replace("\r\n", "\n", file_get_contents(base_path($path)))), 'The original baseline migration changed: '.$path);
        }
        $this->artisan('migrate', ['--database' => 'refactor_upgrade', '--path' => array_keys($fixture['migrations']), '--force' => true])->assertSuccessful();
        $this->assertSame(count($fixture['migrations']), DB::table('migrations')->count());
        $this->assertFalse(Schema::hasTable('feature_usage_receipts'));
        $this->assertFalse(Schema::hasColumn('parser_runs', 'source_request_count'));
        $this->assertSame('InnoDB', DB::selectOne("SHOW TABLE STATUS WHERE Name = 'feature_usage_daily'")->Engine);

        $user = User::factory()->create(['id' => 11]);
        $this->attachSubscription($user);
        $staff = User::factory()->create(['id' => 12, 'account_type' => User::ACCOUNT_TYPE_ADMIN]);
        $missing = User::factory()->create(['id' => 13]);
        $zero = User::factory()->create(['id' => 14]);
        $before = [];
        foreach (self::modules() as $module => $definition) {
            $table = $this->reportTable($module);
            $this->assertFalse(Schema::hasColumn($table, 'quota_receipt_id'));
            foreach ([[$user, 3], [$staff, 1], [$zero, 0]] as [$owner, $used]) {
                DB::table('feature_usage_daily')->insert(['user_id' => $owner->id, 'feature' => $this->resource($module), 'usage_date' => '2026-10-10', 'used' => $used, 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ([
                [$user, 'pending', true], [$user, 'processing', true], [$user, 'pending', false],
                [$staff, 'pending', true], [$user, 'completed', true], [$missing, 'pending', true], [$zero, 'pending', true],
            ] as $index => [$owner, $status, $charged]) {
                $this->seedReport($module, $definition, $owner, $index + 1, $status, $charged);
            }
            $before[$table] = $this->rows($table);
            $before[$this->scheduleTable($module)] = $this->rows($this->scheduleTable($module));
        }
        $checkpoint = json_decode($fixture['parser_checkpoint'], true, flags: JSON_THROW_ON_ERROR);
        $path = 'youtube-parser-runs/11/'.$checkpoint['runId'].'.json';
        Storage::disk('private')->put($path, $fixture['parser_checkpoint']);
        DB::table('parser_runs')->insert([
            'run_id' => $checkpoint['runId'], 'user_id' => 11, 'module' => 'youtube', 'status' => 'running', 'stage' => $checkpoint['stage'],
            'progress' => $checkpoint['progress'], 'file_disk' => 'private', 'file_path' => $path,
            'file_size_bytes' => strlen($fixture['parser_checkpoint']), 'started_at' => now(), 'last_activity_at' => now(),
            'expires_at' => now()->addDays(7), 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($fixture['jobs'] as $name => $command) {
            DB::table('jobs')->insert([
                'queue' => 'legacy-upgrade-'.$name, 'payload' => json_encode(['job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'data' => ['command' => $command]], JSON_THROW_ON_ERROR),
                'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp,
            ]);
        }
        foreach (['feature_usage_daily', 'parser_runs', 'jobs', 'users', 'user_subscriptions'] as $table) {
            $before[$table] = $this->rows($table);
        }

        $this->artisan('migrate', ['--database' => 'refactor_upgrade', '--force' => true])->assertSuccessful();
        $this->assertSame(count($fixture['migrations']) + 2, DB::table('migrations')->count());
        $this->assertSame($fixture['parser_checkpoint'], Storage::disk('private')->get($path));
        foreach ($before as $table => $rows) {
            $after = $this->rows($table);
            foreach ($after as &$row) {
                unset($row['quota_receipt_id'], $row['source_request_count']);
            }
            unset($row);
            $this->assertSame($rows, $after, 'Upgrade changed legacy fields in '.$table);
        }
        $this->assertSame(10, DB::table('feature_usage_receipts')->count());
        foreach (self::modules() as $module => $definition) {
            $table = $this->reportTable($module);
            foreach (range(1, 7) as $reportId) {
                $receipt = DB::table($table)->where('id', $reportId)->value('quota_receipt_id');
                if ($reportId <= 2) {
                    $this->assertNotNull($receipt);
                    $this->assertDatabaseHas('feature_usage_receipts', ['id' => $receipt, 'released_at' => null]);
                } else {
                    $this->assertNull($receipt);
                }
            }
            $this->exerciseLegacyReport($module, $definition);
        }
        $this->exerciseLegacyParser($checkpoint);
        $migrations = $this->rows('migrations');
        $receipts = $this->rows('feature_usage_receipts');
        $this->artisan('migrate', ['--database' => 'refactor_upgrade', '--force' => true])->assertSuccessful();
        $this->assertSame($migrations, $this->rows('migrations'));
        $this->assertSame($receipts, $this->rows('feature_usage_receipts'));
        Http::assertNothingSent();
    }

    private function exerciseLegacyReport(string $module, array $definition): void
    {
        $table = $this->reportTable($module);
        $receiptId = DB::table($table)->where('id', 1)->value('quota_receipt_id');
        if ($module === 'site_intel') {
            $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->once()->andReturn(['8.8.8.8']);
        }
        $this->mock($definition['source'])->shouldReceive($definition['method'])->once()->andThrow(new RuntimeException('Synthetic source failure after upgrade'));
        $payload = json_decode(DB::table('jobs')->where('queue', 'legacy-upgrade-'.$module)->value('payload'), true, flags: JSON_THROW_ON_ERROR);
        $job = unserialize($payload['data']['command'], ['allowed_classes' => [$definition['job']]]);
        $this->assertInstanceOf($definition['job'], $job);
        $this->assertSame(1, $job->reportId);
        $this->assertSame(self::TOKEN, $job->token);
        $generator = app($definition['generator']);
        $job->handle($generator);
        $this->assertDatabaseHas($table, ['id' => 1, 'status' => 'pending', 'attempt_count' => 1, 'quota_charged' => true, 'quota_receipt_id' => $receiptId]);
        $this->assertSame(3, $this->usage($module));
        $job->handle($generator); // Duplicate old delivery cannot consume quota again.
        $this->assertSame(3, $this->usage($module));
        $generator->fail(2, self::TOKEN, 'synthetic_terminal_failure');
        $generator->fail(2, self::TOKEN, 'synthetic_terminal_failure');
        $this->assertSame(2, $this->usage($module));
        $this->assertDatabaseHas($table, ['id' => 2, 'status' => 'failed', 'quota_charged' => false]);
        DB::table($table)->where('id', 1)->update(['lease_token' => self::TOKEN]);
        $generator->fail(1, self::TOKEN, 'synthetic_terminal_failure');
        $generator->fail(1, self::TOKEN, 'synthetic_terminal_failure');
        $this->assertSame(1, $this->usage($module));
        $this->assertNotNull(DB::table('feature_usage_receipts')->where('id', $receiptId)->value('released_at'));
    }

    private function exerciseLegacyParser(array $checkpoint): void
    {
        $payload = json_decode(DB::table('jobs')->where('queue', 'legacy-upgrade-parser')->value('payload'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('queuedAt', $payload['data']['command']);
        $job = unserialize($payload['data']['command'], ['allowed_classes' => [ProcessParserRun::class]]);
        $this->assertInstanceOf(ProcessParserRun::class, $job);
        $this->assertNull($job->queuedAt);
        $this->assertSame($checkpoint['cursor']['stepRetryUntil'], $job->retryUntil());
        $this->assertSame(3, $job->checkpointVersion);
        $store = app(YouTubeParserRunStore::class);
        $this->assertSame($checkpoint, $store->get(11, $job->runId));
        $this->assertSame(0, ParserRun::query()->where('run_id', $job->runId)->value('source_request_count'));
        $coordinator = app(ParserRunExecutionCoordinator::class);
        $advance = static function (array $state): array {
            app(ParserRunSourceRequestBudget::class)->charge();
            $state['progress']++;

            return $state;
        };
        $after = $coordinator->advance($store, 11, $job->runId, $advance, checkpointVersion: $job->checkpointVersion);
        $this->assertSame($checkpoint['data'], $after['data']);
        $this->assertSame($checkpoint['createdAt'], $after['createdAt']);
        $this->assertSame(4, $after['cursor']['checkpointVersion']);
        $this->assertSame(1, ParserRun::query()->where('run_id', $job->runId)->value('source_request_count'));
        $this->assertNull($coordinator->advance($store, 11, $job->runId, fn () => $this->fail('A stale old job must not collect.'), checkpointVersion: 3));
        $this->travel(3)->seconds();
        app()->forgetInstance(ParserRunSourceRequestBudget::class);
        $continued = app(ParserRunExecutionCoordinator::class)->advance($store, 11, $job->runId, $advance, checkpointVersion: 4);
        $this->assertSame($checkpoint['data'], $continued['data']);
        $this->assertSame(2, ParserRun::query()->where('run_id', $job->runId)->value('source_request_count'));
    }

    private function seedReport(string $module, array $definition, User $user, int $id, string $status, bool $charged): void
    {
        $schedule = ['user_id' => $user->id, 'name' => 'Synthetic legacy '.$id, $definition['inputs'] => '["example"]', 'interval' => '1', 'send_time' => '09:00', 'timezone' => 'UTC', 'next_run_at' => now(), 'created_at' => now(), 'updated_at' => now()];
        $report = ['id' => $id, 'user_id' => $user->id, $definition['target'] => 'example'.$id, 'scheduled_for' => now(), 'date_from' => now()->subDay(), 'date_to' => now(), 'status' => $status, 'quota_charged' => $charged, 'quota_charged_at' => $charged ? now() : null, 'lease_token' => self::TOKEN, 'lease_until' => now()->addMinutes(5), 'created_at' => now(), 'updated_at' => now()];
        if ($module === 'site_intel') {
            $schedule['report_type'] = $report['report_type'] = 'analytics';
            $report['target_url'] = 'https://example.org/legacy-'.$id;
        }
        $report['schedule_id'] = DB::table($this->scheduleTable($module))->insertGetId($schedule);
        DB::table($this->reportTable($module))->insert($report);
    }

    private function rows(string $table, string $order = 'id'): array
    {
        return DB::table($table)->orderBy($order)->get()->map(static fn ($row): array => (array) $row)->all();
    }

    private function usage(string $module): int
    {
        return (int) DB::table('feature_usage_daily')->where('user_id', 11)->where('feature', $this->resource($module))->where('usage_date', '2026-10-10')->value('used');
    }

    private function resource(string $module): string
    {
        return str_replace('_', '-', $module).'.analytics';
    }

    private function reportTable(string $module): string
    {
        return $module === 'site_intel' ? 'site_intel_scheduled_reports' : $module.'_analytics_reports';
    }

    private function scheduleTable(string $module): string
    {
        return $module === 'site_intel' ? 'site_intel_report_schedules' : $module.'_analytics_schedules';
    }
}
