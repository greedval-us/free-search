<?php

namespace App\Modules\NewsMediaIntel\Application\Services;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsMediaIntelServiceInterface;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionDeduplicator;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsSentimentAnalyzer;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsTimelineBuilder;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsTopicExtractor;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMediaIntelResultDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\SentimentSummaryDTO;

final class NewsMediaIntelService implements NewsMediaIntelServiceInterface
{
    public function __construct(
        private readonly NewsFeedFetcherInterface $newsFeedFetcher,
        private readonly NewsMentionDeduplicator $deduplicator,
        private readonly NewsTopicExtractor $topicExtractor,
        private readonly NewsTimelineBuilder $timelineBuilder,
        private readonly NewsSentimentAnalyzer $sentimentAnalyzer,
    ) {
    }

    public function monitor(string $query): NewsMediaIntelResultDTO
    {
        $q = trim($query);

        if ($q === '') {
            return new NewsMediaIntelResultDTO(
                query: '',
                mentions: [],
                topics: [],
                timeline: [],
                sentiment: new SentimentSummaryDTO(positive: 0, neutral: 0, negative: 0),
            );
        }

        $mentions = $this->newsFeedFetcher->fetchAll($q);

        $mentions = $this->deduplicator->deduplicate($mentions);
        $mentions = array_slice($mentions, 0, 120);

        return new NewsMediaIntelResultDTO(
            query: $q,
            mentions: $mentions,
            topics: $this->topicExtractor->extract($mentions),
            timeline: $this->timelineBuilder->build($mentions),
            sentiment: $this->sentimentAnalyzer->summarize($mentions),
        );
    }
}
