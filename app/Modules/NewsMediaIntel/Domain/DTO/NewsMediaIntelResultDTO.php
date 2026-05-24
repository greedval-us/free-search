<?php

namespace App\Modules\NewsMediaIntel\Domain\DTO;

use App\Support\Contracts\ArrayPayloadable;

final class NewsMediaIntelResultDTO implements ArrayPayloadable
{
    /**
     * @param array<int, NewsMentionDTO> $mentions
     * @param array<int, NewsTopicDTO> $topics
     * @param array<int, TimelinePointDTO> $timeline
     */
    public function __construct(
        public readonly string $query,
        public readonly array $mentions,
        public readonly array $topics,
        public readonly array $timeline,
        public readonly SentimentSummaryDTO $sentiment,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'mentions' => array_map(static fn (NewsMentionDTO $item): array => $item->toArray(), $this->mentions),
            'topics' => array_map(static fn (NewsTopicDTO $item): array => $item->toArray(), $this->topics),
            'timeline' => array_map(static fn (TimelinePointDTO $item): array => $item->toArray(), $this->timeline),
            'sentiment' => $this->sentiment->toArray(),
        ];
    }
}
