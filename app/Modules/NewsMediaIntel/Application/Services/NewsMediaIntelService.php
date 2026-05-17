<?php

namespace App\Modules\NewsMediaIntel\Application\Services;

use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionDeduplicator;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsSentimentAnalyzer;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsTimelineBuilder;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsTopicExtractor;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\Providers\BingNewsRssProvider;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\Providers\GoogleNewsRssProvider;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMediaIntelResultDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\SentimentSummaryDTO;

final class NewsMediaIntelService
{
    public function __construct(
        private readonly GoogleNewsRssProvider $googleNewsRssProvider,
        private readonly BingNewsRssProvider $bingNewsRssProvider,
        private readonly NewsMentionDeduplicator $deduplicator,
        private readonly NewsTopicExtractor $topicExtractor,
        private readonly NewsTimelineBuilder $timelineBuilder,
        private readonly NewsSentimentAnalyzer $sentimentAnalyzer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function monitor(string $query): array
    {
        $q = trim($query);

        if ($q === '') {
            return (new NewsMediaIntelResultDTO(
                query: '',
                mentions: [],
                topics: [],
                timeline: [],
                sentiment: new SentimentSummaryDTO(positive: 0, neutral: 0, negative: 0),
            ))->toArray();
        }

        $mentions = [
            ...$this->googleNewsRssProvider->fetch($q),
            ...$this->bingNewsRssProvider->fetch($q),
        ];

        $mentions = $this->deduplicator->deduplicate($mentions);
        $mentions = array_slice($mentions, 0, 120);

        return (new NewsMediaIntelResultDTO(
            query: $q,
            mentions: $mentions,
            topics: $this->topicExtractor->extract($mentions),
            timeline: $this->timelineBuilder->build($mentions),
            sentiment: $this->sentimentAnalyzer->summarize($mentions),
        ))->toArray();
    }
}
