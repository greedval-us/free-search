<?php

namespace App\Modules\Username;

use App\Modules\Username\Contracts\UsernameSourceCheckerInterface;
use App\Modules\Username\DTO\UsernameSearchQueryDTO;
use App\Modules\Username\DTO\UsernameSearchResultDTO;
use App\Modules\Username\DTO\UsernameSearchSummaryDTO;

final class UsernameSearchService
{
    public function __construct(
        private readonly UsernameSourceCatalog $sourceCatalog,
        private readonly UsernameSourceCheckerInterface $sourceChecker,
    ) {
    }

    public function search(UsernameSearchQueryDTO $query): UsernameSearchResultDTO
    {
        $sources = $this->sourceCatalog->all();
        $items = $this->sourceChecker->checkMany($sources, $query->username);

        return new UsernameSearchResultDTO(
            username: $query->username,
            checkedAt: now()->toIso8601String(),
            summary: UsernameSearchSummaryDTO::fromResults($items),
            items: $items,
        );
    }
}
