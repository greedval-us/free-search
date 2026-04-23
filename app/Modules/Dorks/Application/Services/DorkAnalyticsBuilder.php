<?php

namespace App\Modules\Dorks\Application\Services;

use App\Modules\Dorks\Domain\DTO\DorkResultItemDTO;

final class DorkAnalyticsBuilder
{
    /**
     * @param array<int, DorkResultItemDTO> $items
     * @return array<string, mixed>
     */
    public function build(array $items, string $target): array
    {
        $goalCounts = [];
        $sourceCounts = [];
        $domainCounts = [];

        foreach ($items as $item) {
            $goalCounts[$item->goal] = ($goalCounts[$item->goal] ?? 0) + 1;
            $sourceCounts[$item->source] = ($sourceCounts[$item->source] ?? 0) + 1;

            if ($item->domain !== null && $item->domain !== '') {
                $domainCounts[$item->domain] = ($domainCounts[$item->domain] ?? 0) + 1;
            }
        }

        arsort($domainCounts);

        return [
            'goalDistribution' => $this->toDistribution($goalCounts),
            'sourceDistribution' => $this->toDistribution($sourceCounts),
            'topDomains' => $this->toDistribution(array_slice($domainCounts, 0, 10, true), preserveLabels: true),
            'graph' => $this->buildGraph($target, $goalCounts, $sourceCounts, $domainCounts, $items),
        ];
    }

    /**
     * @param array<string, int> $counts
     * @return array<int, array{key: string, label: string, count: int}>
     */
    private function toDistribution(array $counts, bool $preserveLabels = true): array
    {
        arsort($counts);
        $rows = [];

        foreach ($counts as $key => $count) {
            $rows[] = [
                'key' => (string) $key,
                'label' => $preserveLabels ? (string) $key : strtoupper((string) $key),
                'count' => (int) $count,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, int> $goalCounts
     * @param array<string, int> $sourceCounts
     * @param array<string, int> $domainCounts
     * @param array<int, DorkResultItemDTO> $items
     * @return array<string, mixed>
     */
    private function buildGraph(
        string $target,
        array $goalCounts,
        array $sourceCounts,
        array $domainCounts,
        array $items
    ): array {
        $nodes = [];
        $edges = [];

        $targetId = 'target:' . mb_strtolower($target);
        $nodes[$targetId] = [
            'id' => $targetId,
            'type' => 'username',
            'label' => $target,
        ];

        foreach ($goalCounts as $goal => $count) {
            $goalId = 'goal:' . $goal;
            $nodes[$goalId] = [
                'id' => $goalId,
                'type' => 'category',
                'label' => $goal,
                'confidence' => $count,
            ];

            $edges[] = [
                'source' => $targetId,
                'target' => $goalId,
                'kind' => 'category',
                'confidence' => $count,
            ];
        }

        foreach ($sourceCounts as $source => $count) {
            $sourceId = 'source:' . $source;
            $nodes[$sourceId] = [
                'id' => $sourceId,
                'type' => 'region',
                'label' => $source,
                'confidence' => $count,
            ];
        }

        foreach (array_slice($domainCounts, 0, 20, true) as $domain => $count) {
            $domainId = 'domain:' . $domain;
            $nodes[$domainId] = [
                'id' => $domainId,
                'type' => 'domain',
                'label' => $domain,
                'confidence' => $count,
            ];
        }

        $pairCounts = [];
        foreach ($items as $item) {
            $pairKey = $item->goal . '|' . $item->source;
            $pairCounts[$pairKey] = ($pairCounts[$pairKey] ?? 0) + 1;
        }

        foreach ($pairCounts as $pairKey => $count) {
            [$goal, $source] = explode('|', $pairKey, 2);
            $edges[] = [
                'source' => 'goal:' . $goal,
                'target' => 'source:' . $source,
                'kind' => 'region',
                'confidence' => $count,
            ];
        }

        $domainSourceCounts = [];
        foreach ($items as $item) {
            if ($item->domain === null || $item->domain === '') {
                continue;
            }

            $domainKey = 'domain:' . $item->domain;
            if (!isset($nodes[$domainKey])) {
                continue;
            }

            $pairKey = $item->source . '|' . $item->domain;
            $domainSourceCounts[$pairKey] = ($domainSourceCounts[$pairKey] ?? 0) + 1;
        }

        foreach ($domainSourceCounts as $pairKey => $count) {
            [$source, $domain] = explode('|', $pairKey, 2);
            $edges[] = [
                'source' => 'source:' . $source,
                'target' => 'domain:' . $domain,
                'kind' => 'domain',
                'confidence' => $count,
            ];
        }

        return [
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ];
    }
}

