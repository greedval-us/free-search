<?php

namespace App\Modules\NewsMediaIntel\Monitoring;

/** Optional incremental interface; the existing manual fetchAll contract is unchanged. */
interface NewsPageFetcher
{
    public function fetchPage(string $query, int $page, ?string $language = null): NewsFeedPage;
}
