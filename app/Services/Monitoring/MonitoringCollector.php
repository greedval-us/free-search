<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringCollection;
use App\Models\MonitoringMaterial;
use App\Models\MonitoringProject;
use App\Models\MonitoringSource;
use App\Services\Access\Contracts\FeatureUsageCounterInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final readonly class MonitoringCollector
{
    public function __construct(private SourceAdapterRegistry $adapters, private MonitoringAccess $access,
        private FeatureUsageCounterInterface $usage) {}

    public function validate(int $id, int $owner, int $generation, int $projectGeneration): void
    {
        $source = DB::transaction(function () use ($id, $owner, $generation, $projectGeneration): ?MonitoringSource {
            $source = MonitoringSource::query()->with('project.user')->find($id);
            if ($source === null || $source->project->user_id !== $owner || $source->generation !== $generation
                || $source->project->generation !== $projectGeneration || $source->status !== 'pending' || ! $this->access->canRun($source->project)) {
                return null;
            }
            $token = (string) Str::uuid();
            $attempts = $source->attempts + ($source->lease_token !== null && $source->lease_until?->lte(now()) ? 1 : 0);
            if ($attempts >= config('monitoring.max_attempts')) {
                $source->update(['status' => 'error', 'error' => 'worker_timeout', 'lease_token' => null, 'lease_until' => null]);

                return null;
            }
            $claimed = MonitoringSource::query()->whereKey($id)->where('generation', $generation)->where('status', 'pending')
                ->where(fn ($q) => $q->whereNull('lease_until')->orWhere('lease_until', '<=', now()))
                ->update(['lease_token' => $token, 'attempts' => $attempts, 'lease_until' => now()->addSeconds(config('monitoring.lease_seconds'))]);

            return $claimed ? $source->fresh(['project.user']) : null;
        });
        if ($source === null) {
            return;
        }
        try {
            $resolved = $this->adapters->for($source->platform)->resolve($source->input);
        } catch (Throwable $exception) {
            $this->validationFailure($source, $projectGeneration, $exception);

            return;
        }
        try {
            DB::transaction(function () use ($source, $projectGeneration, $resolved): void {
                $project = MonitoringProject::query()->lockForUpdate()->find($source->project_id);
                $current = MonitoringSource::query()->lockForUpdate()->find($source->id);
                if ($project === null || $current === null || $project->generation !== $projectGeneration || ! $this->access->canRun($project)
                    || $current->generation !== $source->generation || $current->lease_token !== $source->lease_token || $current->status !== 'pending') {
                    return;
                }
                if ($current->identity !== null && $current->identity !== $resolved['identity']) {
                    throw new SourceUnavailable('identity_changed');
                }
                $current->forceFill(['identity' => $resolved['identity'], 'identity_hash' => hash('sha256', $resolved['identity']),
                    'title' => mb_substr($resolved['title'], 0, 255), 'configuration' => $resolved['configuration'], 'status' => 'ready', 'error' => null,
                    'attempts' => 0, 'lease_token' => null, 'lease_until' => null, 'next_collect_at' => now()])->save();
            });
        } catch (QueryException) {
            $this->validationFailure($source, $projectGeneration, new SourceUnavailable('duplicate_source'));
        } catch (SourceUnavailable $e) {
            $this->validationFailure($source, $projectGeneration, $e);
        }
    }

    private function validationFailure(MonitoringSource $source, int $projectGeneration, Throwable $exception): void
    {
        $reason = $exception instanceof SourceUnavailable ? $exception->reason : 'source_unavailable';
        $retry = $exception instanceof SourceUnavailable ? $exception->retryAfter : null;
        DB::transaction(function () use ($source, $projectGeneration, $reason, $retry): void {
            $project = MonitoringProject::query()->lockForUpdate()->find($source->project_id);
            if ($project === null || $project->generation !== $projectGeneration || ! $this->access->canRun($project)) {
                return;
            }
            $current = MonitoringSource::query()->whereKey($source->id)->where('generation', $source->generation)->where('lease_token', $source->lease_token)->lockForUpdate()->first();
            if ($current === null || $current->status !== 'pending') {
                return;
            }
            $attempts = $current->attempts + 1;
            $permanent = str_contains($reason, 'credential') || str_contains($reason, 'session_unavailable') || str_contains($reason, '_not_configured')
                || str_contains($reason, '_configuration') || str_starts_with($reason, 'invalid') || in_array($reason, ['identity_changed', 'duplicate_source', 'group_unavailable'], true);
            $pending = ! $permanent && $attempts < config('monitoring.max_attempts');
            $current->forceFill(['status' => $pending ? 'pending' : (str_contains($reason, 'credential') || str_contains($reason, 'session_unavailable')
                || str_contains($reason, '_not_configured') || str_contains($reason, '_configuration') ? 'missing_credentials' : 'error'),
                'error' => $reason, 'attempts' => $attempts, 'lease_token' => null, 'lease_until' => null, 'dispatched_at' => null,
                'next_collect_at' => $pending ? now()->addSeconds(max($retry ?? 0, min(3600, 30 * (2 ** $attempts)))) : null])->save();
        });
    }

    public function collect(int $id, int $owner): void
    {
        $collection = $this->claim($id, $owner);
        if ($collection === null) {
            return;
        }
        try {
            $page = $this->adapters->for($collection->source->platform)->fetch($collection->source,
                $collection->start_at, $collection->end_at, $collection->cursor);
            if ($page->retryAfter !== null) {
                throw new SourceUnavailable('rate_limited', $page->retryAfter);
            }
            $this->commit($collection, $page);
        } catch (Throwable $exception) {
            $this->failure($collection, $exception);
        }
    }

    private function claim(int $id, int $owner): ?MonitoringCollection
    {
        return DB::transaction(function () use ($id, $owner): ?MonitoringCollection {
            $collection = MonitoringCollection::query()->with('source.project.user')->find($id);
            if ($collection === null || $collection->user_id !== $owner || ! $this->valid($collection)
                || ! in_array($collection->status, ['queued', 'running'], true) || $collection->next_attempt_at?->isFuture()) {
                return null;
            }
            $token = (string) Str::uuid();
            $attempts = $collection->attempts + ($collection->lease_token !== null && $collection->lease_until?->lte(now()) ? 1 : 0);
            if ($attempts >= config('monitoring.max_attempts')) {
                $collection->update(['status' => 'failed', 'error' => 'worker_timeout', 'lease_token' => null, 'lease_until' => null]);
                $collection->source->update(['status' => 'error', 'error' => 'worker_timeout']);

                return null;
            }
            $claimed = MonitoringCollection::query()->whereKey($id)->whereIn('status', ['queued', 'running'])
                ->where(fn ($q) => $q->whereNull('lease_until')->orWhere('lease_until', '<=', now()))
                ->update(['status' => 'running', 'attempts' => $attempts, 'lease_token' => $token, 'lease_until' => now()->addSeconds(config('monitoring.lease_seconds'))]);

            return $claimed ? $collection->fresh(['source.project.user']) : null;
        });
    }

    private function valid(MonitoringCollection $collection): bool
    {
        $source = $collection->source;
        $project = $source?->project;

        return $source !== null && $project !== null && $project->user_id === $collection->user_id
            && $project->generation === $collection->generation && $source->generation === $collection->source_generation
            && in_array($source->status, ['ready', 'collecting'], true) && $project->collection_enabled && $this->access->canRun($project);
    }

    private function commit(MonitoringCollection $claimed, CollectionPage $page): void
    {
        DB::transaction(function () use ($claimed, $page): void {
            MonitoringProject::query()->whereKey($claimed->source->project_id)->lockForUpdate()->first();
            $source = MonitoringSource::query()->lockForUpdate()->find($claimed->source_id);
            $collection = MonitoringCollection::query()->lockForUpdate()->find($claimed->id);
            if ($source === null || $collection === null) {
                return;
            }
            $collection->setRelation('source', $source->load('project.user'));
            if (! $this->valid($collection) || $collection->lease_token !== $claimed->lease_token || $collection->lease_until?->isPast()) {
                return;
            }
            $history = $collection->cursor_history ?? [];
            if (! $page->complete && ($page->cursor === null || in_array($page->cursor, $history, true) || $page->cursor === $collection->cursor)) {
                throw new SourceUnavailable('pagination_stalled');
            }
            $warnings = array_values(array_unique(array_merge($collection->warnings ?? [], $page->warnings)));
            $quota = false;
            $added = 0;
            $truncated = count($page->items) > config('monitoring.page_size');
            if ($truncated) {
                $warnings[] = 'page_item_limit';
            }
            foreach (array_slice($page->items, 0, (int) config('monitoring.page_size')) as $item) {
                if ($collection->items_count + $added >= config('monitoring.max_items_per_window')) {
                    $truncated = true;
                    $warnings[] = 'collection_limit';
                    break;
                }
                if (! $this->matches($source->project, $item + ['platform' => $source->platform])) {
                    continue;
                }
                $published = isset($item['published_at']) ? CarbonImmutable::parse($item['published_at'])->utc() : null;
                if ($published !== null && ($published->lt($collection->start_at) || $published->gte($collection->end_at))) {
                    continue;
                }
                if ($published === null) {
                    $warnings[] = 'unknown_publication_date';
                }
                $url = (string) ($item['url'] ?? '');
                if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                    $warnings[] = 'invalid_material_url';

                    continue;
                }
                $external = (string) ($item['external_id'] ?? '');
                if ($external === '') {
                    $warnings[] = 'missing_material_identity';

                    continue;
                }
                $hash = hash('sha256', $external);
                $material = MonitoringMaterial::query()->where('source_id', $source->id)->where('external_hash', $hash)->first();
                if ($material === null) {
                    $used = $this->usage->consume($source->project->user, 'monitoring.material', $this->access->limits($source->project->user)['items_daily']);
                    if ($used === null) {
                        $quota = true;
                        $warnings[] = 'item_quota';
                        break;
                    }
                    $material = new MonitoringMaterial(['source_id' => $source->id, 'external_hash' => $hash]);
                    $added++;
                }
                $material->fill(['project_id' => $source->project_id, 'user_id' => $collection->user_id, 'platform' => $source->platform,
                    'external_id' => mb_strcut($external, 0, 2048, 'UTF-8'), 'url' => mb_strcut($url, 0, 4096, 'UTF-8'),
                    'title' => mb_strcut((string) ($item['title'] ?? ''), 0, 2048, 'UTF-8'),
                    'text' => mb_strcut((string) ($item['text'] ?? ''), 0, (int) config('monitoring.max_text_bytes'), 'UTF-8'),
                    'author' => isset($item['author']) ? mb_substr((string) $item['author'], 0, 512) : null,
                    'published_at' => $published, 'collected_at' => $material->collected_at ?? now(), 'metrics' => $this->safeMetrics($item['metrics'] ?? []), 'generation' => $source->project->generation])->save();
            }
            $pages = $collection->pages + 1;
            $limited = $quota || $truncated || $pages >= config('monitoring.max_pages')
                || $collection->items_count + $added >= config('monitoring.max_items_per_window');
            if ($limited && ! $page->complete) {
                $warnings[] = 'collection_limit';
            }
            $complete = $page->complete && ! $quota && ! $truncated;
            $terminal = $complete || $limited;
            if ($page->cursor !== null) {
                $history[] = $page->cursor;
            }
            $warnings = array_values(array_unique($warnings));
            $collection->forceFill(['status' => $terminal ? ($complete && $warnings === [] ? 'completed' : 'partial') : 'queued',
                'cursor' => $page->cursor, 'cursor_history' => $history, 'pages' => $pages, 'items_count' => $collection->items_count + $added,
                'warnings' => $warnings, 'attempts' => 0, 'lease_token' => null, 'lease_until' => null, 'dispatched_at' => null,
                'next_attempt_at' => now()->addSeconds(config('monitoring.step_delay_seconds')), 'completed_at' => $terminal ? now() : null])->save();
            $source->forceFill(['status' => 'ready', 'cursor' => $terminal ? null : $page->cursor,
                'last_collected_at' => $terminal ? now() : $source->last_collected_at, 'warnings' => $warnings, 'error' => null,
                'coverage_start' => $source->coverage_start ?? $collection->start_at,
                'coverage_end' => $terminal ? $collection->end_at : $source->coverage_end,
                'next_collect_at' => $terminal && $collection->end_at->gte(now()->subSeconds(2))
                    ? now()->addMinutes(max($source->project->collect_interval_minutes, $this->access->limits($source->project->user)['min_interval_minutes'])) : now()])->save();
        }, 3);
    }

    public function matches(MonitoringProject $project, array $item): bool
    {
        $filters = $project->filters ?? [];
        $text = mb_strtolower((string) ($item['title'] ?? '').' '.(string) ($item['text'] ?? ''));
        foreach ($filters['exclude'] ?? [] as $term) {
            if ($term !== '' && str_contains($text, mb_strtolower($term))) {
                return false;
            }
        }
        if ($project->mode === 'topic') {
            $included = false;
            foreach ($filters['include'] ?? [] as $term) {
                if ($term !== '' && str_contains($text, mb_strtolower($term))) {
                    $included = true;
                    break;
                }
            }
            if (! $included) {
                return false;
            }
        }

        return ($item['platform'] ?? null) !== 'telegram' || empty($filters['author']) || (string) ($item['author'] ?? '') === (string) $filters['author'];
    }

    private function safeMetrics(mixed $metrics): array
    {
        if (! is_array($metrics)) {
            return [];
        }

        return array_filter(array_intersect_key($metrics, array_flip(['views', 'likes', 'replies', 'reposts', 'comments', 'shares', 'favourites', 'boosts', 'forwards', 'reblogs'])), static fn ($v) => is_numeric($v));
    }

    private function failure(MonitoringCollection $claimed, Throwable $exception): void
    {
        DB::transaction(function () use ($claimed, $exception): void {
            MonitoringProject::query()->whereKey($claimed->source->project_id)->lockForUpdate()->first();
            $collection = MonitoringCollection::query()->lockForUpdate()->find($claimed->id);
            if ($collection === null || $collection->lease_token !== $claimed->lease_token || ! $this->valid($collection->load('source.project.user'))) {
                return;
            }
            $attempts = $collection->attempts + 1;
            $terminal = $attempts >= config('monitoring.max_attempts');
            $reason = $exception instanceof SourceUnavailable ? $exception->reason : 'collection_failed';
            $retry = $exception instanceof SourceUnavailable ? $exception->retryAfter : null;
            $collection->forceFill(['status' => $terminal ? 'failed' : 'queued', 'error' => $reason, 'attempts' => $attempts,
                'next_attempt_at' => now()->addSeconds(max($retry ?? 0, min(3600, 30 * (2 ** min($attempts, 6))))),
                'lease_token' => null, 'lease_until' => null, 'dispatched_at' => null, 'completed_at' => $terminal ? now() : null])->save();
            $collection->source->forceFill(['status' => $terminal ? 'error' : 'ready', 'error' => $reason])->save();
        });
    }
}
