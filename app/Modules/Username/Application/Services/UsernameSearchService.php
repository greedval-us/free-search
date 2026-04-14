<?php

namespace App\Modules\Username\Application\Services;

use App\Modules\Username\Domain\Contracts\UsernameSourceCheckerInterface;
use App\Modules\Username\Domain\DTO\UsernameSearchQueryDTO;
use App\Modules\Username\Domain\DTO\UsernameSearchResultDTO;
use App\Modules\Username\Domain\DTO\UsernameSearchSummaryDTO;
use App\Modules\Username\Domain\Services\UsernameConfidenceSummaryBuilder;
use App\Modules\Username\Domain\Services\UsernameEntityGraphBuilder;
use App\Modules\Username\Infrastructure\Cache\UsernameResultCache;
use App\Modules\Username\Infrastructure\Catalog\UsernameSourceCatalog;

final class UsernameSearchService
{
    public function __construct(
        private readonly UsernameSourceCatalog $sourceCatalog,
        private readonly UsernameSourceCheckerInterface $sourceChecker,
        private readonly UsernameResultCache $resultCache,
        private readonly UsernameConfidenceSummaryBuilder $confidenceSummaryBuilder,
        private readonly UsernameSimilarityAnalyzer $similarityAnalyzer,
        private readonly UsernameEntityGraphBuilder $entityGraphBuilder,
    ) {
    }

    public function search(UsernameSearchQueryDTO $query): UsernameSearchResultDTO
    {
        $sources = $this->sourceCatalog->all();
        $username = mb_strtolower(trim($query->username));
        $searchTtl = (int) config('username.cache.search_ttl_seconds', 300);

        $items = $this->resultCache->rememberSearch(
            $username,
            now()->addSeconds($searchTtl),
            fn (): array => $this->sourceChecker->checkMany($sources, $username)
        );

        $analytics = [
            'confidence' => $this->confidenceSummaryBuilder->build($items),
            'similarity' => $this->similarityAnalyzer->analyze($username, $sources),
            'graph' => $this->entityGraphBuilder->build($username, $items),
        ];

        return new UsernameSearchResultDTO(
            username: $username,
            checkedAt: now()->toIso8601String(),
            summary: UsernameSearchSummaryDTO::fromResults($items),
            items: $items,
            analytics: $analytics,
        );
    }
}
