<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringCollection;
use App\Models\MonitoringProject;
use App\Models\MonitoringReport;
use App\Models\MonitoringSchedule;
use App\Models\MonitoringSource;
use App\Models\User;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final readonly class MonitoringManager
{
    private const PROJECT_FIELDS = ['name', 'mode', 'filters', 'language', 'timezone', 'collection_enabled', 'delivery_enabled', 'attach_files', 'empty_delivery', 'collect_interval_minutes'];

    public function __construct(private MonitoringAccess $access, private MonitoringCalendar $calendar,
        private MonitoringDispatch $dispatch, private FeatureAccessServiceInterface $quota) {}

    public function create(User $user, array $data): MonitoringProject
    {
        return DB::transaction(function () use ($user, $data): MonitoringProject {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->access->checkCapacity($user, 'saved_projects', MonitoringProject::query()->where('user_id', $user->id)->count());
            $this->access->checkCapacity($user, 'active_projects', MonitoringProject::query()->where('user_id', $user->id)->where('status', 'active')->count());
            $values = array_replace(['mode' => 'overview', 'filters' => ['include' => [], 'exclude' => []], 'language' => 'ru',
                'timezone' => config('monitoring.timezone'), 'delivery_enabled' => false, 'attach_files' => false,
                'empty_delivery' => 'send', 'collection_enabled' => true, 'collect_interval_minutes' => $this->access->limits($user)['min_interval_minutes']], Arr::only($data, self::PROJECT_FIELDS));
            $values['collect_interval_minutes'] = max($values['collect_interval_minutes'], $this->access->limits($user)['min_interval_minutes']);

            return MonitoringProject::query()->create($values + ['user_id' => $user->id, 'status' => 'active', 'generation' => 1]);
        });
    }

    public function update(MonitoringProject $project, array $data): MonitoringProject
    {
        return DB::transaction(function () use ($project, $data): MonitoringProject {
            $project = MonitoringProject::query()->lockForUpdate()->findOrFail($project->id);
            $this->access->assertUser($project->user);
            $values = Arr::only($data, self::PROJECT_FIELDS);
            if (isset($values['collect_interval_minutes'])) {
                $values['collect_interval_minutes'] = max($values['collect_interval_minutes'], $this->access->limits($project->user)['min_interval_minutes']);
            }
            $changesCollection = (isset($values['mode']) && $values['mode'] !== $project->mode)
                || (isset($values['filters']) && $values['filters'] !== $project->filters)
                || (isset($values['collection_enabled']) && (bool) $values['collection_enabled'] !== $project->collection_enabled);
            $project->fill($values);
            if ($project->isDirty()) {
                $project->generation++;
                $project->save();
                $this->cancelWork($project);
                if ($changesCollection) {
                    $project->sources()->where('status', '!=', 'removed')->update(['collect_from' => now(), 'cursor' => null,
                        'coverage_start' => null, 'coverage_end' => null, 'warnings' => json_encode(['configuration_gap']), 'next_collect_at' => now()]);
                }
            }

            return $project->fresh();
        });
    }

    public function addSource(MonitoringProject $project, array $data): MonitoringSource
    {
        $source = DB::transaction(function () use ($project, $data): MonitoringSource {
            $project = MonitoringProject::query()->lockForUpdate()->findOrFail($project->id);
            $this->access->checkCapacity($project->user, 'sources', $project->sources()->where('status', '!=', 'removed')->count());
            $input = trim($data['input']);
            if ($project->sources()->where('platform', $data['platform'])->where('input', $input)->where('status', '!=', 'removed')->exists()) {
                throw ValidationException::withMessages(['input' => __('monitoring.duplicate_source')]);
            }
            $project->increment('generation');
            $this->cancelWork($project);

            return $project->sources()->create(['platform' => $data['platform'], 'input' => $input, 'status' => 'pending',
                'collect_from' => CarbonImmutable::now('UTC')->subDays(config('monitoring.initial_lookback_days'))->startOfDay(),
                'next_collect_at' => now(), 'generation' => 1]);
        });
        $this->dispatch->source($source);

        return $source->fresh();
    }

    public function validateSource(MonitoringSource $source): void
    {
        $this->access->assertUser($source->project->user);
        abort_if($source->status === 'removed', 404);
        $source->forceFill(['status' => 'pending', 'error' => null, 'attempts' => 0, 'lease_token' => null,
            'lease_until' => null, 'generation' => $source->generation + 1, 'next_collect_at' => now()])->save();
        $source->collections()->whereIn('status', ['queued', 'running'])->update(['status' => 'cancelled', 'lease_token' => null, 'lease_until' => null]);
        $this->dispatch->source($source->fresh());
    }

    public function removeSource(MonitoringSource $source): void
    {
        DB::transaction(function () use ($source): void {
            $project = MonitoringProject::query()->lockForUpdate()->findOrFail($source->project_id);
            $this->access->assertUser($project->user);
            $project->increment('generation');
            $this->cancelWork($project);
            $source->forceFill(['status' => 'removed', 'identity_hash' => null, 'generation' => $source->generation + 1, 'lease_token' => null, 'lease_until' => null])->save();
        });
    }

    public function lifecycle(MonitoringProject $project, string $status): void
    {
        DB::transaction(function () use ($project, $status): void {
            $project = MonitoringProject::query()->lockForUpdate()->findOrFail($project->id);
            $this->access->assertUser($project->user);
            if ($project->status === $status) {
                return;
            }
            if ($status === 'active') {
                User::query()->whereKey($project->user_id)->lockForUpdate()->firstOrFail();
                $this->access->checkCapacity($project->user, 'active_projects', MonitoringProject::query()->where('user_id', $project->user_id)->where('status', 'active')->count());
                $project->sources()->where('status', '!=', 'removed')->update(['collect_from' => now(), 'cursor' => null, 'coverage_start' => null, 'coverage_end' => null,
                    'warnings' => json_encode(['pause_gap']), 'next_collect_at' => now()]);
                foreach ($project->schedules()->get() as $schedule) {
                    $schedule->forceFill(['next_run_at' => $this->calendar->next($schedule, CarbonImmutable::now('UTC'))])->save();
                }
            }
            $project->forceFill(['status' => $status, 'generation' => $project->generation + 1])->save();
            $this->cancelWork($project);
        });
    }

    public function saveSchedule(MonitoringProject $project, array $data, ?MonitoringSchedule $schedule = null): MonitoringSchedule
    {
        return DB::transaction(function () use ($project, $data, $schedule): MonitoringSchedule {
            $project = MonitoringProject::query()->lockForUpdate()->findOrFail($project->id);
            $this->access->assertUser($project->user);
            abort_if($schedule !== null && $schedule->project_id !== $project->id, 404);
            if ($schedule === null && $project->schedules()->count() >= 4) {
                throw ValidationException::withMessages(['period' => __('monitoring.limit')]);
            }
            $schedule = $schedule === null ? new MonitoringSchedule(['project_id' => $project->id, 'generation' => 0])
                : $project->schedules()->lockForUpdate()->findOrFail($schedule->id);
            if (array_key_exists('anchor_date', $data) && $data['anchor_date'] === null) {
                unset($data['anchor_date']);
            }
            $schedule->fill(array_replace(['enabled' => true, 'delivery_enabled' => false, 'time' => '09:00', 'timezone' => $project->timezone,
                'anchor_date' => CarbonImmutable::now($project->timezone)->toDateString()], Arr::only($data, ['period', 'enabled', 'delivery_enabled', 'time', 'timezone', 'anchor_date'])));
            $schedule->generation++;
            $schedule->next_run_at = $this->calendar->next($schedule, CarbonImmutable::now('UTC'));
            $schedule->save();
            MonitoringReport::query()->where('schedule_id', $schedule->id)->whereIn('status', ['queued', 'building'])->update(['status' => 'cancelled', 'lease_token' => null, 'lease_until' => null]);

            return $schedule;
        });
    }

    public function requestReport(MonitoringProject $project, string $period, string $requestKey, ?MonitoringReport $regenerate = null): MonitoringReport
    {
        $report = $this->makeReport($project, $period, hash('sha256', 'manual:'.$project->id.':'.$requestKey),
            CarbonImmutable::now('UTC'), null, $regenerate);
        $this->dispatch->report($report);

        return $report->fresh();
    }

    public function makeReport(MonitoringProject $project, string $period, string $key, CarbonImmutable $at,
        ?MonitoringSchedule $schedule = null, ?MonitoringReport $regenerate = null): MonitoringReport
    {
        return DB::transaction(function () use ($project, $period, $key, $at, $schedule, $regenerate): MonitoringReport {
            $project = MonitoringProject::query()->lockForUpdate()->findOrFail($project->id);
            $existing = MonitoringReport::query()->where('request_key', $key)->first();
            if ($existing !== null) {
                return $existing;
            }
            if (! $this->access->canRun($project)) {
                throw ValidationException::withMessages(['monitoring' => __('monitoring.inactive')]);
            }
            abort_if($regenerate !== null && ($regenerate->project_id !== $project->id || ! $regenerate->available()), 404);
            $decision = $this->quota->consumeResource($project->user, 'monitoring.report');
            if (! $decision->allowed) {
                throw ValidationException::withMessages(['monitoring' => $decision->message]);
            }
            $timezone = $schedule?->timezone ?? $project->timezone;
            [$start, $end] = $regenerate === null ? $this->calendar->period($period, $timezone, $at) : [$regenerate->start_at, $regenerate->end_at];
            $trigger = $regenerate?->trigger_key ?? $key;
            $version = $regenerate === null ? 1 : (int) MonitoringReport::query()->where('trigger_key', $trigger)->max('version') + 1;
            $sources = $project->sources()->where('status', '!=', 'removed')->get()->map(fn ($s) => ['id' => $s->id, 'platform' => $s->platform,
                'identity' => $s->identity, 'title' => $s->title, 'generation' => $s->generation])->all();
            $report = MonitoringReport::query()->create(['user_id' => $project->user_id, 'project_id' => $project->id,
                'schedule_id' => $schedule?->id, 'trigger_key' => $trigger, 'request_key' => $key, 'version' => $version,
                'period' => $regenerate?->period ?? $period, 'timezone' => $timezone, 'start_at' => $start, 'end_at' => $end, 'cutoff_at' => CarbonImmutable::now('UTC'),
                'expires_at' => CarbonImmutable::now('UTC')->addDays($this->access->limits($project->user)['retention_days']),
                'configuration' => ['name' => $project->name, 'mode' => $project->mode, 'filters' => $project->filters, 'language' => $project->language,
                    'generation' => $project->generation, 'schedule_generation' => $schedule?->generation, 'sources' => $sources], 'next_attempt_at' => now()]);
            $project->sources()->where('status', 'ready')->where(fn ($q) => $q->whereNull('coverage_end')->orWhere('coverage_end', '<', $end))
                ->update(['next_collect_at' => now()]);

            return $report;
        });
    }

    public function remove(MonitoringProject $project): void
    {
        $this->lifecycle($project, 'archived');
        Storage::disk('private')->deleteDirectory('monitoring/'.$project->user_id.'/'.$project->id);
        $project->delete();
    }

    private function cancelWork(MonitoringProject $project): void
    {
        MonitoringCollection::query()->whereIn('source_id', $project->sources()->select('id'))->whereIn('status', ['queued', 'running'])
            ->update(['status' => 'cancelled', 'lease_token' => null, 'lease_until' => null]);
        $project->sources()->where('status', 'collecting')->update(['status' => 'ready', 'lease_token' => null, 'lease_until' => null]);
        $project->reports()->whereIn('status', ['queued', 'building'])->update(['status' => 'cancelled', 'lease_token' => null, 'lease_until' => null]);
    }
}
