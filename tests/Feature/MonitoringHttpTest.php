<?php

namespace Tests\Feature;

use App\Jobs\Monitoring\BuildMonitoringReport;
use App\Jobs\Monitoring\ValidateMonitoringSource;
use App\Models\MonitoringProject;
use App\Models\MonitoringReport;
use App\Models\User;
use App\Services\Monitoring\MonitoringManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MonitoringHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_unverified_and_blocked_users_cannot_use_monitoring(): void
    {
        $this->get(route('monitoring.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())->getJson(route('monitoring.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['is_blocked' => true]))->getJson(route('monitoring.index'))->assertRedirect(route('login'));
    }

    public function test_creates_overview_without_keywords_and_exactly_one_schedule(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('monitoring.projects.store'), $this->input())->assertCreated();
        $project = MonitoringProject::query()->sole();
        $this->assertSame($user->id, $project->user_id);
        $this->assertSame('overview', $project->mode);
        $this->assertSame([], $project->filters['include']);
        $this->assertFalse($project->delivery_enabled);
        $this->assertSame(1, $project->schedules()->count());
        $this->assertSame('day', $project->schedules()->sole()->period);
        Http::assertNothingSent();
    }

    public function test_validates_topic_phrases_timezone_and_nested_configuration(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('monitoring.projects.store'),
            $this->input(['mode' => 'topic', 'timezone' => 'Mars/Olympus', 'filters' => ['include' => [], 'exclude' => [], 'author' => null, 'token' => 'unexpected']]))
            ->assertUnprocessable()->assertJsonValidationErrors(['filters', 'filters.include', 'timezone']);
        $this->assertDatabaseCount('monitoring_projects', 0);
    }

    public function test_other_users_project_and_children_are_not_disclosed(): void
    {
        Bus::fake([ValidateMonitoringSource::class]);
        $owner = User::factory()->create();
        $project = app(MonitoringManager::class)->create($owner, $this->input());
        $source = $project->sources()->create(['platform' => 'telegram', 'input' => '@channel']);
        $schedule = $project->schedules()->create(['period' => 'day', 'timezone' => 'UTC', 'anchor_date' => '2026-10-06']);
        $report = $this->report($project);
        $this->actingAs(User::factory()->create());
        foreach (['monitoring.projects.show', 'monitoring.projects.status', 'monitoring.materials'] as $name) {
            $this->getJson(route($name, $project->id))->assertNotFound();
        }
        $this->patchJson(route('monitoring.projects.update', $project->id), $this->input())->assertNotFound();
        $this->postJson(route('monitoring.sources.validate', [$project->id, $source->id]))->assertNotFound();
        $this->deleteJson(route('monitoring.sources.destroy', [$project->id, $source->id]))->assertNotFound();
        $this->patchJson(route('monitoring.schedules.update', [$project->id, $schedule->id]),
            ['period' => 'day', 'enabled' => false, 'delivery_enabled' => false, 'time' => '09:00', 'timezone' => 'UTC'])->assertNotFound();
        $this->getJson(route('monitoring.reports.show', $report->id))->assertNotFound();
        $this->getJson(route('monitoring.reports.download', [$report->id, 'json']))->assertNotFound();
        Bus::assertNothingDispatched();
    }

    public function test_source_verification_is_queued_and_reading_status_performs_no_network_call(): void
    {
        Http::preventStrayRequests();
        Bus::fake([ValidateMonitoringSource::class]);
        $project = $this->project();
        $this->postJson(route('monitoring.sources.store', $project->id), ['platform' => 'telegram', 'input' => '@channel'])->assertCreated();
        Bus::assertDispatched(ValidateMonitoringSource::class);
        $response = $this->getJson(route('monitoring.projects.status', $project->id))->assertOk()->assertJsonPath('project.sources.0.status', 'pending');
        $this->assertStringNotContainsString('lease_token', $response->getContent());
        $this->assertStringNotContainsString('configuration', $response->getContent());
        Http::assertNothingSent();
    }

    public function test_manual_report_http_retries_reuse_one_version(): void
    {
        Bus::fake([BuildMonitoringReport::class]);
        $project = $this->project();
        $input = ['period' => 'day', 'request_key' => (string) Str::uuid()];
        $first = $this->postJson(route('monitoring.reports.store', $project->id), $input)->assertAccepted();
        $this->postJson(route('monitoring.reports.store', $project->id), $input)->assertAccepted()->assertJsonPath('id', $first->json('id'));
        $this->assertDatabaseCount('monitoring_reports', 1);
        Bus::assertDispatched(BuildMonitoringReport::class);
    }

    public function test_download_serves_saved_private_file_and_respects_file_states(): void
    {
        Http::preventStrayRequests();
        Storage::fake('private');
        $project = $this->project();
        $report = $this->report($project);
        $this->get(route('monitoring.reports.download', [$report->id, 'json']))->assertConflict();
        Storage::disk('private')->put('monitoring/test.json', '{"saved":true}');
        $report->update(['file_status' => 'ready', 'files' => ['json' => 'monitoring/test.json']]);
        $this->get(route('monitoring.reports.download', [$report->id, 'json']))->assertOk()->assertDownload()->assertHeader('X-Content-Type-Options', 'nosniff');
        $report->update(['expires_at' => now()->subSecond()]);
        $this->get(route('monitoring.reports.download', [$report->id, 'json']))->assertGone();
        Http::assertNothingSent();
    }

    public function test_archive_retains_history_and_explicit_delete_requires_confirmation(): void
    {
        Storage::fake('private');
        $project = $this->project();
        $report = $this->report($project);
        $this->postJson(route('monitoring.projects.lifecycle', $project->id), ['status' => 'archived'])->assertOk();
        $this->assertDatabaseHas('monitoring_reports', ['id' => $report->id]);
        $this->deleteJson(route('monitoring.projects.destroy', $project->id))->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->deleteJson(route('monitoring.projects.destroy', $project->id), ['confirmation' => 'delete'])->assertOk();
        $this->assertDatabaseMissing('monitoring_projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('monitoring_reports', ['id' => $report->id]);
    }

    public function test_history_is_paginated_and_filters_only_owned_reports(): void
    {
        $this->withoutVite();
        $project = $this->project();
        $this->report($project);
        $other = app(MonitoringManager::class)->create(User::factory()->create(), $this->input(['name' => 'Other']));
        $this->report($other);
        $this->get(route('monitoring.history', ['search' => 'Monitoring', 'period' => 'day', 'status' => 'completed']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('monitoring/History')->has('reports.data', 1)->where('reports.total', 1));
    }

    private function project(): MonitoringProject
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return app(MonitoringManager::class)->create($user, $this->input());
    }

    private function report(MonitoringProject $project): MonitoringReport
    {
        return $project->reports()->create(['user_id' => $project->user_id, 'trigger_key' => hash('sha256', (string) Str::uuid()),
            'status' => 'completed', 'period' => 'day', 'timezone' => 'UTC', 'start_at' => now()->subDay()->startOfDay(),
            'end_at' => now()->startOfDay(), 'cutoff_at' => now(), 'expires_at' => now()->addDays(90),
            'completed_at' => now(), 'configuration' => ['name' => $project->name], 'summary' => ['count' => 0], 'coverage' => []]);
    }

    private function input(array $overrides = []): array
    {
        return array_replace(['name' => 'Monitoring', 'mode' => 'overview', 'filters' => ['include' => [], 'exclude' => [], 'author' => null],
            'language' => 'en', 'timezone' => 'UTC', 'delivery_enabled' => false, 'attach_files' => false,
            'empty_delivery' => 'skip', 'collection_enabled' => true, 'collect_interval_minutes' => 360], $overrides);
    }
}
