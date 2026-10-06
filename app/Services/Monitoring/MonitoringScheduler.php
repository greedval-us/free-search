<?php

namespace App\Services\Monitoring;

use App\Integrations\TelegramBot\MonitoringDelivery;
use App\Models\MonitoringCollection;
use App\Models\MonitoringProject;
use App\Models\MonitoringReport;
use App\Models\MonitoringSchedule;
use App\Models\MonitoringSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class MonitoringScheduler
{
    public function __construct(private MonitoringAccess $access, private MonitoringCalendar $calendar,
        private MonitoringManager $manager, private MonitoringDispatch $dispatch, private MonitoringDelivery $delivery) {}

    public function tick(): void
    {
        $limit = (int) config('monitoring.tick_limit');
        foreach (MonitoringSchedule::query()->whereHas('project', fn ($q) => $q->where('status', 'active')->whereHas('user', fn ($q) => $q->where('is_blocked', false)->whereNotNull('email_verified_at')))
            ->where('enabled', true)->where('next_run_at', '<=', now())->orderBy('next_run_at')->limit($limit)->get() as $schedule) {
            $this->schedule($schedule);
        }
        foreach (MonitoringSource::query()->whereHas('project', fn ($q) => $q->where('status', 'active')->where('collection_enabled', true)->whereHas('user', fn ($q) => $q->where('is_blocked', false)->whereNotNull('email_verified_at')))
            ->with('project.user')->whereIn('status', ['pending', 'ready', 'collecting'])
            ->where('next_collect_at', '<=', now())->orderBy('next_collect_at')->limit($limit)->get() as $source) {
            if (! $this->access->canRun($source->project)) {
                $this->manager->lifecycle($source->project, 'paused');

                continue;
            }
            if ($source->status === 'pending') {
                if ($this->due($source) && ($source->lease_until === null || $source->lease_until->lte(now()))) {
                    $this->dispatch->source($source);
                }
            } else {
                $this->window($source);
            }
        }
        foreach (MonitoringCollection::query()->whereIn('status', ['queued', 'running'])
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('lease_until')->orWhere('lease_until', '<=', now()))->orderBy('id')->limit($limit)->get() as $collection) {
            $source = $collection->source;
            $project = $source?->project;
            if ($project === null || ! $this->access->canRun($project) || ! $project->collection_enabled
                || $project->generation !== $collection->generation || $source->generation !== $collection->source_generation || $source->status === 'removed') {
                $collection->update(['status' => 'cancelled', 'lease_token' => null, 'lease_until' => null]);

                continue;
            }
            if ($this->due($collection)) {
                $this->dispatch->collection($collection);
            }
        }
        foreach (MonitoringReport::query()->whereHas('user', fn ($q) => $q->where('is_blocked', false)->whereNotNull('email_verified_at'))
            ->where('expires_at', '>', now())->whereIn('status', ['queued', 'building', ...MonitoringReport::FINAL_STATUSES])
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('lease_until')->orWhere('lease_until', '<=', now()))
            ->where(fn ($q) => $q->whereIn('status', ['queued', 'building'])->orWhereIn('file_status', ['pending', 'working'])
                ->orWhere(fn ($q) => $q->where('file_status', 'ready')->where(fn ($q) => $q->whereNull('delivery_status')->orWhere('delivery_status', 'pending'))))
            ->orderBy('id')->limit($limit)->get() as $report) {
            if (in_array($report->status, ['queued', 'building'], true) && (! $this->access->canRun($report->project)
                || $report->project->generation !== ($report->configuration['generation'] ?? null))) {
                $report->update(['status' => 'cancelled', 'lease_token' => null, 'lease_until' => null]);

                continue;
            }
            if (in_array($report->status, MonitoringReport::FINAL_STATUSES, true) && $report->file_status === 'ready') {
                $this->delivery->enqueue($report);
            } elseif ($this->due($report)) {
                $this->dispatch->report($report);
            }
        }
    }

    private function due(object $model): bool
    {
        return $model->dispatched_at === null || $model->dispatched_at->lte(now()->subSeconds(config('monitoring.recovery_seconds')));
    }

    private function schedule(MonitoringSchedule $candidate): void
    {
        $report = DB::transaction(function () use ($candidate): ?MonitoringReport {
            $project = MonitoringProject::query()->lockForUpdate()->find($candidate->project_id);
            $schedule = MonitoringSchedule::query()->lockForUpdate()->find($candidate->id);
            if ($project === null || $schedule === null || ! $schedule->enabled || $schedule->next_run_at?->isFuture()) {
                return null;
            }
            if (! $this->access->canRun($project)) {
                $this->manager->lifecycle($project, 'paused');

                return null;
            }
            $due = $schedule->next_run_at;
            $now = CarbonImmutable::now('UTC');
            // Keep only the most recent missed occurrence; no historical notification avalanche.
            $last = $this->calendar->previous($schedule, $now);
            $skipped = $this->calendar->skipped($schedule, $due, $last);
            $future = $this->calendar->next($schedule, $now);
            $key = hash('sha256', 'schedule:'.$schedule->id.':'.$schedule->generation.':'.$last->toISOString());
            try {
                $report = $skipped === 0 || config('monitoring.catch_up_reports') > 0
                    ? $this->manager->makeReport($project, $schedule->period, $key, $last, $schedule) : null;
                if ($report === null) {
                    $skipped++;
                }
            } catch (ValidationException) {
                $report = null;
                $skipped++;
            }
            $schedule->forceFill(['next_run_at' => $future, 'last_run_at' => $last, 'missed_runs' => $schedule->missed_runs + $skipped])->save();

            return $report;
        });
        if ($report !== null) {
            $this->dispatch->report($report);
        }
    }

    private function window(MonitoringSource $candidate): void
    {
        DB::transaction(function () use ($candidate): void {
            $project = MonitoringProject::query()->lockForUpdate()->find($candidate->project_id);
            $source = MonitoringSource::query()->lockForUpdate()->find($candidate->id);
            if ($project === null || $source === null || ! $this->access->canRun($project) || ! $project->collection_enabled
                || ! in_array($source->status, ['ready', 'collecting'], true) || $source->next_collect_at?->isFuture()) {
                return;
            }
            if ($source->collections()->whereIn('status', ['queued', 'running'])->exists()) {
                return;
            }
            $start = $source->coverage_end ?? $source->collect_from ?? CarbonImmutable::now('UTC');
            $start = $start->max($source->collect_from ?? $start);
            $end = $start->addHours(config('monitoring.window_hours'))->min(CarbonImmutable::now('UTC')->startOfSecond());
            if ($end->lte($start)) {
                return;
            }
            $key = hash('sha256', $source->id.':'.$project->generation.':'.$source->generation.':'.$start->toISOString().':'.$end->toISOString());
            $source->collections()->firstOrCreate(['trigger_key' => $key], ['user_id' => $project->user_id, 'generation' => $project->generation,
                'source_generation' => $source->generation, 'start_at' => $start, 'end_at' => $end, 'next_attempt_at' => now()]);
            $source->update(['status' => 'collecting']);
        });
    }
}
