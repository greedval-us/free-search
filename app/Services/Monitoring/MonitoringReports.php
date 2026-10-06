<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringCollection;
use App\Models\MonitoringMaterial;
use App\Models\MonitoringProject;
use App\Models\MonitoringReport;
use App\Models\MonitoringReportItem;
use App\Models\MonitoringSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final readonly class MonitoringReports
{
    public function __construct(private MonitoringAccess $access, private MonitoringCollector $collector, private MonitoringSummary $summaries) {}

    public function build(int $id, int $owner): void
    {
        $report = DB::transaction(function () use ($id, $owner): ?MonitoringReport {
            $report = MonitoringReport::query()->with('project.user', 'schedule')->find($id);
            if ($report === null || $report->user_id !== $owner || ! $this->valid($report)
                || ! in_array($report->status, ['queued', 'building'], true) || $report->next_attempt_at?->isFuture()) {
                return null;
            }
            $token = (string) Str::uuid();
            $attempts = $report->attempts + ($report->lease_token !== null && $report->lease_until?->lte(now()) ? 1 : 0);
            if ($attempts >= config('monitoring.max_attempts')) {
                $report->update(['status' => 'failed', 'error' => 'worker_timeout', 'lease_token' => null, 'lease_until' => null]);

                return null;
            }
            $claimed = MonitoringReport::query()->whereKey($id)->whereIn('status', ['queued', 'building'])
                ->where(fn ($q) => $q->whereNull('lease_until')->orWhere('lease_until', '<=', now()))
                ->update(['status' => 'building', 'attempts' => $attempts, 'lease_token' => $token, 'lease_until' => now()->addSeconds(config('monitoring.lease_seconds'))]);

            return $claimed ? $report->fresh(['project.user', 'schedule']) : null;
        });
        if ($report === null) {
            return;
        }
        try {
            $coverage = $this->coverage($report);
            $complete = $coverage !== [] && ! in_array(false, array_column($coverage, 'complete'), true);
            if (! $complete && $report->created_at->addSeconds(config('monitoring.report_wait_seconds'))->isFuture()) {
                MonitoringReport::query()->whereKey($id)->where('lease_token', $report->lease_token)->update(['status' => 'queued',
                    'lease_token' => null, 'lease_until' => null, 'dispatched_at' => null, 'next_attempt_at' => now()->addSeconds(30)]);

                return;
            }
            $this->snapshot($report, $coverage);
        } catch (Throwable) {
            $attempts = $report->attempts + 1;
            MonitoringReport::query()->whereKey($id)->where('lease_token', $report->lease_token)->update([
                'status' => $attempts >= config('monitoring.max_attempts') ? 'failed' : 'queued', 'attempts' => $attempts, 'error' => 'generation_failed',
                'lease_token' => null, 'lease_until' => null, 'dispatched_at' => null, 'next_attempt_at' => now()->addMinutes(1)]);
        }
    }

    private function valid(MonitoringReport $report): bool
    {
        $project = $report->project;

        return $project !== null && $project->user_id === $report->user_id && $this->access->canRun($project)
            && $project->generation === ($report->configuration['generation'] ?? null)
            && ($report->schedule_id === null || ($report->schedule?->enabled && $report->schedule->generation === ($report->configuration['schedule_generation'] ?? null)));
    }

    public function coverage(MonitoringReport $report): array
    {
        $result = [];
        foreach ($report->configuration['sources'] ?? [] as $source) {
            $currentSource = MonitoringSource::query()->where('project_id', $report->project_id)->find($source['id']);
            $rows = MonitoringCollection::query()->where('source_id', $source['id'])->where('generation', $report->configuration['generation'])
                ->where('source_generation', $source['generation'])->where('start_at', '<', $report->end_at)->where('end_at', '>', $report->start_at)
                ->orderBy('start_at')->get();
            $cursor = $report->start_at;
            $warnings = $currentSource?->error ? [$currentSource->error] : [];
            $gaps = [];
            foreach ($rows as $window) {
                $warnings = array_merge($warnings, $window->warnings ?? [], $window->error ? [$window->error] : []);
                if ($window->status !== 'completed') {
                    continue;
                }
                if ($window->start_at->gt($cursor)) {
                    $gaps[] = ['start' => $cursor->toISOString(), 'end' => $window->start_at->min($report->end_at)->toISOString()];
                }
                if ($window->end_at->gt($cursor)) {
                    $cursor = $window->end_at->min($report->end_at);
                }
            }
            if ($cursor->lt($report->end_at)) {
                $gaps[] = ['start' => $cursor->toISOString(), 'end' => $report->end_at->toISOString()];
            }
            $result[] = $source + ['complete' => $gaps === [], 'gaps' => $gaps, 'warnings' => array_values(array_unique($warnings)),
                'state' => $gaps === [] ? 'covered' : (in_array($currentSource?->status, ['error', 'missing_credentials'], true) ? $currentSource->status
                    : ($rows->isEmpty() ? 'insufficient_history' : 'partial'))];
        }

        return $result;
    }

    private function snapshot(MonitoringReport $claimed, array $coverage): void
    {
        DB::transaction(function () use ($claimed, $coverage): void {
            $project = MonitoringProject::query()->lockForUpdate()->find($claimed->project_id);
            $report = MonitoringReport::query()->lockForUpdate()->find($claimed->id);
            if ($project === null || $report === null || $report->lease_token !== $claimed->lease_token || $report->lease_until?->isPast()) {
                return;
            }
            $report->setRelation('project', $project->load('user'));
            if (! $this->valid($report->load('schedule'))) {
                return;
            }
            $snapshotProject = new MonitoringProject(['mode' => $report->configuration['mode'], 'filters' => $report->configuration['filters']]);
            $cutoff = CarbonImmutable::now('UTC');
            $sourceIds = array_column($report->configuration['sources'] ?? [], 'id');
            $materials = MonitoringMaterial::query()->where('project_id', $report->project_id)->where('user_id', $report->user_id)
                ->whereIn('source_id', $sourceIds)->where('collected_at', '<=', $cutoff)
                ->where(fn ($q) => $q->where(fn ($q) => $q->where('published_at', '>=', $report->start_at)->where('published_at', '<', $report->end_at))
                    ->orWhere(fn ($q) => $q->whereNull('published_at')->where('collected_at', '>=', $report->start_at)->where('collected_at', '<', $report->end_at)))
                ->orderBy('id');
            // A crashed transaction commits neither a finished version nor a partial item set.
            $report->items()->delete();
            $counts = [];
            $summaryState = [];
            $unknown = 0;
            $total = 0;
            $truncated = false;
            foreach ($materials->lazyById(200) as $material) {
                if (! $this->collector->matches($snapshotProject, $material->toArray())) {
                    continue;
                }
                if ($total >= config('monitoring.max_report_items')) {
                    $truncated = true;
                    break;
                }
                $source = collect($report->configuration['sources'])->firstWhere('id', $material->source_id);
                $item = ['id' => $material->id, 'source_id' => $material->source_id, 'source_identity' => $source['identity'], 'source_title' => $source['title'],
                    'platform' => $material->platform, 'external_id' => $material->external_id, 'url' => $material->url,
                    'title' => $material->title, 'text' => $material->text, 'author' => $material->author,
                    'published_at' => $material->published_at?->toISOString(), 'collected_at' => $material->collected_at->toISOString(), 'metrics' => $material->metrics ?? []];
                MonitoringReportItem::query()->create(['report_id' => $report->id, 'material_id' => $material->id, 'snapshot' => $item]);
                $total++;
                $unknown += $material->published_at === null ? 1 : 0;
                $counts[$material->platform] = ($counts[$material->platform] ?? 0) + 1;
                $this->summaries->accumulate($summaryState, $item, $report->timezone);
            }
            $complete = $coverage !== [] && ! in_array(false, array_column($coverage, 'complete'), true) && ! $truncated && $unknown === 0;
            $lang = $report->configuration['language'];
            $status = $complete ? ($total === 0 ? 'empty' : 'completed') : 'partial';
            $summary = ['title' => $report->configuration['name'], 'introduction' => __('monitoring.summary', [], $lang),
                'message' => __($complete && $total === 0 ? 'monitoring.empty' : ($complete ? 'monitoring.summary' : 'monitoring.partial'), [], $lang),
                'generator' => 'basic-v1', 'count' => $total, 'unknown_date_count' => $unknown,
                'truncated' => $truncated, 'platform_counts' => $counts,
                'comparison' => $this->comparison($report, $coverage, $total, $complete)] + $this->summaries->finish($summaryState);
            $report->forceFill(['status' => $status, 'cutoff_at' => $cutoff, 'summary' => $summary, 'coverage' => $coverage,
                'completed_at' => now(), 'lease_token' => null, 'lease_until' => null, 'dispatched_at' => null,
                'next_attempt_at' => now(), 'error' => null])->save();
        }, 3);
    }

    private function comparison(MonitoringReport $report, array $coverage, int $count, bool $complete): array
    {
        $previous = MonitoringReport::query()->where('project_id', $report->project_id)->where('period', $report->period)
            ->where('end_at', $report->start_at)->whereIn('status', ['completed', 'empty'])->latest('version')->first();
        $comparable = $complete && $previous !== null && $previous->timezone === $report->timezone
            && ($previous->configuration['sources'] ?? []) === ($report->configuration['sources'] ?? [])
            && ($previous->configuration['filters'] ?? []) === ($report->configuration['filters'] ?? [])
            && ($previous->configuration['mode'] ?? '') === $report->configuration['mode'];

        return $comparable ? ['available' => true, 'previous_report_id' => $previous->id, 'previous_count' => $previous->summary['count'],
            'current_count' => $count, 'difference' => $count - $previous->summary['count'], 'metric' => 'saved_publications']
            : ['available' => false, 'reason' => __('monitoring.comparison_unavailable', [], $report->configuration['language'])];
    }
}
