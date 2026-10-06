<?php

namespace Tests\Feature\Monitoring;

use App\Jobs\Monitoring\BuildMonitoringReport;
use App\Models\ParserRun;
use App\Models\User;
use App\Services\Monitoring\MonitoringManager;
use App\Services\Monitoring\MonitoringReports;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class MonitoringMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_migration_preserves_existing_accounts_and_manual_parser_runs_on_populated_database(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-10-06 09:00:00', 'UTC'));
        Http::preventStrayRequests();
        config(['telegram_bot.enabled' => false, 'monitoring.report_wait_seconds' => 0, 'inertia.ssr.enabled' => false]);
        $user = User::factory()->create(['name' => 'Existing account', 'email' => 'existing@example.test']);
        $run = ParserRun::query()->create([
            'run_id' => (string) Str::uuid(), 'user_id' => $user->id,
            'module' => 'telegram', 'status' => 'running', 'stage' => 'collecting', 'progress' => 35,
            'file_disk' => 'private', 'file_path' => 'parser-runs/existing/manual.json',
            'started_at' => now()->subHour(), 'last_activity_at' => now()->subMinute(),
        ]);
        $userBefore = $user->fresh()->getAttributes();
        $runBefore = $run->fresh()->getAttributes();

        // RefreshDatabase already applied it: remove only the new feature's tables,
        // then apply the migration against the remaining populated legacy schema.
        $migration = require database_path('migrations/2026_10_05_233105_create_monitoring_tables.php');
        $migration->down();
        $this->assertSame($userBefore, $user->fresh()->getAttributes());
        $this->assertSame($runBefore, $run->fresh()->getAttributes());
        $migration->up();

        Bus::fake([BuildMonitoringReport::class]);
        $manager = app(MonitoringManager::class);
        $project = $manager->create($user, ['name' => 'Project after migration', 'timezone' => 'UTC', 'language' => 'en']);
        $report = $manager->requestReport($project, 'day', (string) Str::uuid());
        app(MonitoringReports::class)->build($report->id, $user->id);
        $this->assertSame('partial', $report->fresh()->status);
        $this->assertSame(0, $report->fresh()->summary['count']);
        $this->assertSame($report->id, $project->reports()->sole()->id);
        $this->assertDatabaseCount('monitoring_projects', 1);
        $this->assertDatabaseCount('monitoring_reports', 1);
        $this->assertDatabaseCount('parser_runs', 1);
        $this->assertSame($userBefore, $user->fresh()->getAttributes());
        $this->assertSame($runBefore, $run->fresh()->getAttributes());
        Bus::assertDispatched(BuildMonitoringReport::class, fn ($job) => $job->reportId === $report->id && $job->ownerId === $user->id);

        $this->actingAs($user)->get('/monitoring/history')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('monitoring/History')
            ->has('reports.data', 1)
            ->where('reports.data.0.id', $report->id)
            ->where('reports.data.0.project_name', 'Project after migration')
            ->where('reports.data.0.status', 'partial'));
    }
}
