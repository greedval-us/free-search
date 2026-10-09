<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionDeduplicator;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Support\Observability\ExternalServiceLogger;

final class SearxngNewsFeedFetcher implements NewsFeedFetcherInterface
{
    public function __construct(
        private readonly NewsMediaIntelConfig $config,
        private readonly ExternalServiceLogger $externalServiceLogger,
        private readonly NewsMentionDeduplicator $deduplicator,
    ) {}

    /** @return array<int, NewsMentionDTO> */
    public function fetchAll(string $query): array
    {
        return (new SearxngSearchClient($this->config, $this->externalServiceLogger, $this->deduplicator))
            ->search($query)->mentions;
    }
}
