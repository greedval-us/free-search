<?php

namespace App\Modules\Dorks\Application\Services;

use App\Modules\Dorks\Application\DTO\DorkSearchQueryDTO;
use App\Modules\Dorks\Domain\Contracts\DorkSourceProviderInterface;
use App\Modules\Dorks\Domain\DTO\DorkResultItemDTO;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

final class DorkSearchService
{
    /**
     * @param array<string, DorkSourceProviderInterface> $providers
     */
    public function __construct(
        private readonly array $providers,
        private readonly DorkAnalyticsBuilder $analyticsBuilder,
        private readonly DorkResultQualityFilter $qualityFilter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function search(DorkSearchQueryDTO $query): array
    {
        $goals = $this->resolveGoals($query->goal);
        if ($goals === []) {
            throw new RuntimeException(__('Selected goal is not supported.'));
        }

        $diagnostics = [
            'attemptedSources' => [],
            'sourceErrors' => [],
        ];
        $collected = [];
        $providers = $this->providers();

        foreach ($goals as $goalKey => $goalConfig) {
            $dorks = $this->goalDorks($goalConfig, $query->target);

            foreach ($dorks as $dorkQuery) {
                foreach ($providers as $sourceKey => $provider) {
                    $startedAt = microtime(true);

                    try {
                        $items = $provider->search($dorkQuery, $goalKey, $this->perProviderLimit());
                        $collected = [...$collected, ...$items];

                        $diagnostics['attemptedSources'][] = [
                            'source' => $sourceKey,
                            'goal' => $goalKey,
                            'ok' => true,
                            'count' => count($items),
                            'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
                        ];
                    } catch (Throwable $exception) {
                        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
                        $diagnostics['attemptedSources'][] = [
                            'source' => $sourceKey,
                            'goal' => $goalKey,
                            'ok' => false,
                            'count' => 0,
                            'durationMs' => $durationMs,
                        ];
                        $diagnostics['sourceErrors'][] = [
                            'source' => $sourceKey,
                            'goal' => $goalKey,
                            'message' => $exception->getMessage(),
                            'durationMs' => $durationMs,
                        ];
                    }
                }
            }
        }

        $qualityFiltered = $this->qualityFilter->filter($collected, $query);
        $items = array_slice($this->deduplicate($qualityFiltered), 0, $this->maxResults());
        $analytics = $this->analyticsBuilder->build($items, $query->target);
        $summary = $this->buildSummary($items, $analytics);

        return [
            'target' => $query->target,
            'goal' => $query->goal,
            'checkedAt' => Carbon::now(config('app.timezone'))->toIso8601String(),
            'summary' => $summary,
            'items' => array_map(
                static fn (DorkResultItemDTO $item): array => $item->toArray(),
                $items
            ),
            'analytics' => $analytics,
            'diagnostics' => $diagnostics,
            'availableGoals' => $this->availableGoals(),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function availableGoals(): array
    {
        $goals = config('osint.dorks.goals', []);
        if (!is_array($goals)) {
            return [];
        }

        $result = [
            ['key' => 'all', 'label' => __('All goals')],
        ];

        foreach ($goals as $key => $goal) {
            if (!is_array($goal)) {
                continue;
            }

            $result[] = [
                'key' => (string) $key,
                'label' => (string) __((string) ($goal['label'] ?? $key)),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resolveGoals(string $goal): array
    {
        $all = config('osint.dorks.goals', []);
        if (!is_array($all)) {
            return [];
        }

        if ($goal === 'all') {
            return array_filter($all, static fn ($value): bool => is_array($value));
        }

        $selected = $all[$goal] ?? null;

        return is_array($selected) ? [$goal => $selected] : [];
    }

    /**
     * @param array<string, mixed> $goalConfig
     * @return array<int, string>
     */
    private function goalDorks(array $goalConfig, string $target): array
    {
        $templates = $goalConfig['templates'] ?? [];
        if (!is_array($templates)) {
            return [];
        }

        $queries = [];
        foreach ($templates as $template) {
            $template = trim((string) $template);
            if ($template === '') {
                continue;
            }

            $queries[] = str_replace('{target}', $target, $template);
            if (count($queries) >= $this->dorksPerGoal()) {
                break;
            }
        }

        return $queries;
    }

    /**
     * @return array<string, DorkSourceProviderInterface>
     */
    private function providers(): array
    {
        $enabledProviders = [];

        foreach ($this->providers as $sourceKey => $provider) {
            if ((bool) config('osint.dorks.sources.' . $sourceKey . '.enabled', true)) {
                $enabledProviders[$sourceKey] = $provider;
            }
        }

        return $enabledProviders;
    }

    private function maxResults(): int
    {
        return max(10, (int) config('osint.dorks.search.max_results', 120));
    }

    private function perProviderLimit(): int
    {
        return max(1, (int) config('osint.dorks.search.per_provider_limit', 20));
    }

    private function dorksPerGoal(): int
    {
        return max(1, (int) config('osint.dorks.search.dorks_per_goal', 2));
    }

    /**
     * @param array<int, DorkResultItemDTO> $items
     * @return array<int, DorkResultItemDTO>
     */
    private function deduplicate(array $items): array
    {
        $map = [];

        foreach ($items as $item) {
            $urlKey = mb_strtolower(trim($item->url));
            $titleKey = mb_strtolower(trim($item->title));
            $key = $urlKey !== '' ? $urlKey : $titleKey;

            if ($key === '' || array_key_exists($key, $map)) {
                continue;
            }

            $map[$key] = $item;
        }

        return array_values($map);
    }

    /**
     * @param array<int, DorkResultItemDTO> $items
     * @param array<string, mixed> $analytics
     * @return array<string, mixed>
     */
    private function buildSummary(array $items, array $analytics): array
    {
        $uniqueDomains = [];
        foreach ($items as $item) {
            if ($item->domain !== null && $item->domain !== '') {
                $uniqueDomains[$item->domain] = true;
            }
        }

        return [
            'total' => count($items),
            'uniqueDomains' => count($uniqueDomains),
            'sources' => count($analytics['sourceDistribution'] ?? []),
            'goals' => count($analytics['goalDistribution'] ?? []),
        ];
    }
}
