<?php

namespace App\Modules\NewsMediaIntel\Application\Services\Marketing;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\NewsMediaIntel\Application\Contracts\SearxngSearchClientInterface;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsSentimentAnalyzer;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsTimelineBuilder;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMarketingLookupDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use Carbon\CarbonImmutable;

final readonly class NewsMarketingAnalyticsService
{
    public function __construct(
        private SearxngSearchClientInterface $search,
        private NewsMediaIntelConfig $config,
        private MarketingMentionSet $mentionSet,
        private NewsSentimentAnalyzer $sentiment,
        private NewsTimelineBuilder $timeline,
        private MarketingBrandComparison $brands,
        private MarketingContentAnalyzer $content,
        private MarketingVisibility $visibility,
    ) {}

    public function analyze(NewsMarketingLookupDTO $lookup): array
    {
        $started = microtime(true);
        $budget = min(25, $this->config->searxngRequestBudgetSeconds());
        $deadline = $started + $budget;
        $categories = $coverage = $results = [];
        $failure = null;
        foreach (['general', 'news'] as $index => $category) {
            $engines = $this->engines($lookup->options->engines, $category);
            $coverage[$category] = $this->emptyCoverage($lookup->options, $category, $engines);
            if ($lookup->options->engines !== [] && $engines === []) {
                $coverage[$category] = [...$coverage[$category], 'status' => 'unavailable', 'stopReason' => 'no_selected_engines'];

                continue;
            }
            $options = new NewsSearchOptionsDTO(categories: [$category], language: $lookup->options->language,
                timeRange: $lookup->options->timeRange, safeSearch: $lookup->options->safeSearch,
                engines: $engines, maxPages: $lookup->options->maxPages);
            try {
                $result = $this->search->search(trim($lookup->query), $options, $index === 0 ? $started + $budget / 2 : $deadline);
                $results[$category] = $result;
                $categories[$category] = $result->mentions;
                $coverage[$category] = [...$coverage[$category], ...$result->coverage, 'status' => 'ok'];
            } catch (ExternalServiceUnavailableException $exception) {
                $failure = $exception;
                $coverage[$category] = [...$coverage[$category], 'status' => 'unavailable', 'stopReason' => 'service_unavailable'];
            }
        }
        if ($results === []) {
            throw $failure ?? new ExternalServiceUnavailableException('errors.news_media_intel.unavailable', 'news_search_unavailable');
        }

        $all = $this->mentionSet->merge($categories);
        $sets = $this->mentionSet->limit($all, $this->config->maxMentions());
        $mentions = array_column($sets, 'mention');
        $coverage['partial'] = count($results) < 2 || count($all) > count($sets)
            || count(array_filter($coverage, static fn (array $item): bool => ($item['truncated'] ?? false) === true || ($item['unresponsiveEngines'] ?? []) !== [])) > 0;
        $coverage['analyticsSampleTruncated'] = count($all) > count($sets);
        $now = CarbonImmutable::now('UTC');
        $dated = $newsDated = [];
        $future = $fresh = $newsCount = $generalCount = 0;
        foreach ($sets as $set) {
            $mention = $set['mention'];
            $news = in_array('news', $set['categories'], true);
            $newsCount += (int) $news;
            $generalCount += (int) in_array('general', $set['categories'], true);
            $date = $this->publicationDate($mention->publishedAt);
            if ($date === null) {
                continue;
            }
            if ($date->greaterThan($now)) {
                $future++;

                continue;
            }
            $dated[] = $mention;
            if ($news) {
                $newsDated[] = $mention;
            }
            $fresh += (int) $date->greaterThanOrEqualTo($now->subDays(7));
        }

        $publishers = $this->publishers($sets);
        $comparison = $this->brands->build($lookup, $mentions);
        $suggestions = $corrections = $answers = $infoboxes = [];
        foreach ($results as $result) {
            $suggestions = [...$suggestions, ...$result->suggestions];
            $corrections = [...$corrections, ...$result->corrections];
            $answers = [...$answers, ...$result->answers];
            $infoboxes = [...$infoboxes, ...$result->infoboxes];
        }
        $suggestions = $this->unique($suggestions);
        $topics = $this->content->topics($mentions);
        $questions = $this->content->questions($mentions, $suggestions);

        return ['query' => trim($lookup->query), 'checkedAt' => $now->toIso8601String(),
            'options' => $this->effectiveOptions($lookup->options, $results),
            'summary' => ['mentions' => count($mentions), 'newsMentions' => $newsCount, 'generalMentions' => $generalCount,
                'publishers' => count($publishers), 'knownDates' => count($dated),
                'undatedMentions' => count($mentions) - count($dated) - $future, 'futureDates' => $future,
                'freshMentions' => $fresh, 'freshnessPercent' => $dated !== [] ? MarketingMentionSet::percent($fresh, count($dated)) : null,
                'entityMatches' => $comparison['totalMatches'], 'sentiment' => $this->sentiment->summarize($mentions)->toArray()],
            'mentions' => array_map(static fn (array $set): array => [...$set['mention']->toArray(),
                'engines' => $set['engines'], 'categories' => $set['categories'],
                'publisher' => MarketingMentionSet::host($set['mention']->link)], $sets),
            'coverage' => $coverage, 'suggestions' => $suggestions, 'corrections' => $this->unique($corrections),
            'answers' => $this->unique($answers), 'infoboxes' => $this->unique($infoboxes),
            'publishers' => $publishers, 'brandComparison' => $comparison, 'topics' => $topics,
            'questions' => $questions,
            'contentOpportunities' => $this->content->opportunities($topics, $questions, $publishers, $comparison, count($mentions)),
            'visibility' => $this->visibility->build($lookup->domain, $sets),
            'timeline' => array_map(static fn ($point): array => $point->toArray(), $this->timeline->build($newsDated))];
    }

    private function engines(array $selected, string $category): array
    {
        if ($selected === []) {
            return [];
        }

        return array_values(array_intersect($selected, (array) config('osint.news_media_intel.searxng.available_engines.'.$category, [])));
    }

    private function effectiveOptions(NewsSearchOptionsDTO $options, array $results): array
    {
        $effective = [...$options->toArray(), 'categories' => ['general', 'news'],
            'language' => $options->language !== '' ? $options->language : $this->config->searxngLanguage(),
            'timeRange' => $options->timeRange ?? $this->config->searxngTimeRange(),
            'safeSearch' => $options->safeSearch === -1 ? $this->config->searxngSafeSearch() : $options->safeSearch,
            'maxPages' => $options->maxPages > 0 ? min(10, $options->maxPages) : $this->config->searxngMaxPages()];
        $engines = [];
        $hasEngineCoverage = false;
        foreach ($results as $result) {
            foreach (['language', 'timeRange', 'maxPages'] as $key) {
                if (array_key_exists($key, $result->coverage)) {
                    $effective[$key] = $result->coverage[$key];
                }
            }
            if (array_key_exists('requestedEngines', $result->coverage)) {
                $engines = [...$engines, ...$result->coverage['requestedEngines']];
                $hasEngineCoverage = true;
            }
        }
        if ($hasEngineCoverage) {
            $effective['engines'] = array_values(array_unique($engines));
        }

        return $effective;
    }

    private function emptyCoverage(NewsSearchOptionsDTO $options, string $category, array $engines): array
    {
        return ['pagesRequested' => 0, 'pagesLoaded' => 0, 'engines' => [], 'unresponsiveEngines' => [],
            'estimatedTotal' => null, 'truncated' => false, 'stopReason' => 'exhausted',
            'categories' => [$category], 'language' => $options->language !== '' ? $options->language : $this->config->searxngLanguage(),
            'timeRange' => $options->timeRange ?? $this->config->searxngTimeRange(), 'requestedEngines' => $engines,
            'maxPages' => $options->maxPages > 0 ? min(10, $options->maxPages) : $this->config->searxngMaxPages()];
    }

    private function publicationDate(string $raw): ?CarbonImmutable
    {
        if (trim($raw) === '') {
            return null;
        }
        $timestamp = strtotime($raw);
        if ($timestamp === false || gmdate('Y-m-d', $timestamp) === '1970-01-01') {
            return null;
        }

        return CarbonImmutable::createFromTimestampUTC($timestamp);
    }

    private function publishers(array $sets): array
    {
        $buckets = [];
        foreach ($sets as $set) {
            $mention = $set['mention'];
            $host = MarketingMentionSet::host($mention->link);
            if ($host === '') {
                continue;
            }
            $buckets[$host] ??= ['host' => $host, 'count' => 0, 'types' => [], 'engines' => [], 'evidenceUrls' => []];
            $buckets[$host]['count']++;
            $buckets[$host]['types'] = array_values(array_unique([...$buckets[$host]['types'], ...$set['categories']]));
            $buckets[$host]['engines'] = array_values(array_unique([...$buckets[$host]['engines'], ...$set['engines']]));
            $buckets[$host]['evidenceUrls'] = array_slice(array_values(array_unique([...$buckets[$host]['evidenceUrls'], $mention->link])), 0, 5);
        }
        $rows = array_values($buckets);
        foreach ($rows as &$row) {
            $row['share'] = MarketingMentionSet::percent($row['count'], count($sets));
        }
        unset($row);
        usort($rows, static fn (array $a, array $b): int => ($b['count'] <=> $a['count']) ?: strcmp($a['host'], $b['host']));

        return $rows;
    }

    private function unique(array $items): array
    {
        $seen = $result = [];
        foreach ($items as $item) {
            $key = is_string($item) ? mb_strtolower(trim($item)) : serialize($item);
            if ($key !== '' && ! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $item;
            }
        }

        return array_slice($result, 0, 30);
    }
}
