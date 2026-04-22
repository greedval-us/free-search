<?php

namespace App\Modules\Fio\Application\Services;

use App\Modules\Fio\Infrastructure\Http\FioDuckDuckGoClient;
use Carbon\Carbon;

class FioPublicSearchService
{
    public function __construct(
        private readonly FioDuckDuckGoClient $searchClient,
        private readonly FioAgeExtractor $ageExtractor,
        private readonly FioRegionClassifier $regionClassifier,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $fullName): array
    {
        $normalizedName = $this->normalizeFullName($fullName);
        $rawMatches = $this->searchClient->search($normalizedName);
        $matches = [];

        foreach ($rawMatches as $item) {
            $title = (string) ($item['title'] ?? '');
            $snippet = (string) ($item['snippet'] ?? '');
            $url = (string) ($item['url'] ?? '');
            $domain = $item['domain'] ?? null;
            $searchText = trim($title . ' ' . $snippet . ' ' . $url);

            $age = $this->ageExtractor->extract($searchText);
            $region = $this->regionClassifier->resolve($searchText);

            $matches[] = [
                'title' => $title,
                'snippet' => $snippet,
                'url' => $url,
                'domain' => is_string($domain) ? $domain : null,
                'region' => $region,
                'age' => $age,
                'ageBucket' => $this->ageExtractor->bucket($age),
                'confidence' => $this->confidenceScore($normalizedName, $searchText),
            ];
        }

        $regionClusters = $this->buildRegionClusters($matches);
        $ageClusters = $this->buildAgeClusters($matches);
        $summary = $this->buildSummary($matches, $regionClusters, $ageClusters);

        return [
            'target' => [
                'fullName' => $normalizedName,
            ],
            'checkedAt' => Carbon::now()->toIso8601String(),
            'summary' => $summary,
            'clusters' => [
                'regions' => $regionClusters,
                'ages' => $ageClusters,
            ],
            'matches' => $matches,
            'source' => [
                'provider' => 'DuckDuckGo HTML',
                'mode' => 'online-public',
            ],
        ];
    }

    private function normalizeFullName(string $fullName): string
    {
        $value = trim($fullName);
        $value = preg_replace('/\s+/u', ' ', $value);

        return is_string($value) ? $value : '';
    }

    private function confidenceScore(string $fullName, string $text): int
    {
        $target = mb_strtolower($fullName);
        $haystack = mb_strtolower($text);

        if ($target !== '' && str_contains($haystack, $target)) {
            return 90;
        }

        $parts = array_values(array_filter(explode(' ', $target)));
        $hits = 0;
        foreach ($parts as $part) {
            if ($part !== '' && str_contains($haystack, $part)) {
                $hits++;
            }
        }

        if (count($parts) === 0) {
            return 0;
        }

        $ratio = $hits / count($parts);

        if ($ratio >= 0.8) {
            return 75;
        }

        if ($ratio >= 0.5) {
            return 55;
        }

        return 35;
    }

    /**
     * @param  array<int, array<string, mixed>>  $matches
     * @return array<int, array{key: string, count: int, percent: float}>
     */
    private function buildRegionClusters(array $matches): array
    {
        $total = max(1, count($matches));
        $counts = [];

        foreach ($matches as $item) {
            $key = (string) ($item['region'] ?? 'unknown');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        arsort($counts);
        $clusters = [];

        foreach ($counts as $key => $count) {
            $clusters[] = [
                'key' => $key,
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
            ];
        }

        return $clusters;
    }

    /**
     * @param  array<int, array<string, mixed>>  $matches
     * @return array<int, array{key: string, count: int, percent: float}>
     */
    private function buildAgeClusters(array $matches): array
    {
        $total = max(1, count($matches));
        $order = ['under_18', '18_24', '25_34', '35_44', '45_54', '55_plus', 'unknown'];
        $counts = array_fill_keys($order, 0);

        foreach ($matches as $item) {
            $key = (string) ($item['ageBucket'] ?? 'unknown');
            if (!array_key_exists($key, $counts)) {
                $key = 'unknown';
            }

            $counts[$key]++;
        }

        $clusters = [];
        foreach ($order as $key) {
            $count = (int) ($counts[$key] ?? 0);
            $clusters[] = [
                'key' => $key,
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
            ];
        }

        return $clusters;
    }

    /**
     * @param  array<int, array<string, mixed>>  $matches
     * @param  array<int, array{key: string, count: int, percent: float}>  $regionClusters
     * @param  array<int, array{key: string, count: int, percent: float}>  $ageClusters
     * @return array<string, mixed>
     */
    private function buildSummary(array $matches, array $regionClusters, array $ageClusters): array
    {
        $domains = [];
        $ages = [];

        foreach ($matches as $item) {
            $domain = $item['domain'] ?? null;
            if (is_string($domain) && $domain !== '') {
                $domains[$domain] = true;
            }

            if (is_int($item['age'] ?? null)) {
                $ages[] = (int) $item['age'];
            }
        }

        sort($ages);
        $medianAge = null;

        if (count($ages) > 0) {
            $middle = intdiv(count($ages), 2);
            if (count($ages) % 2 === 0) {
                $medianAge = (int) round(($ages[$middle - 1] + $ages[$middle]) / 2);
            } else {
                $medianAge = $ages[$middle];
            }
        }

        return [
            'matches' => count($matches),
            'domains' => count($domains),
            'topRegion' => $regionClusters[0]['key'] ?? 'unknown',
            'topAgeBucket' => $this->resolveTopAgeBucket($ageClusters),
            'medianAge' => $medianAge,
        ];
    }

    /**
     * @param  array<int, array{key: string, count: int, percent: float}>  $ageClusters
     */
    private function resolveTopAgeBucket(array $ageClusters): string
    {
        $topKey = 'unknown';
        $topCount = -1;

        foreach ($ageClusters as $cluster) {
            if ($cluster['count'] > $topCount) {
                $topCount = $cluster['count'];
                $topKey = $cluster['key'];
            }
        }

        return $topKey;
    }
}
