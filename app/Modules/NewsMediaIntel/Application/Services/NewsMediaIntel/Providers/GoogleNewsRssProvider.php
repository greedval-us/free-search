<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;

final class GoogleNewsRssProvider extends AbstractRssNewsFeedProvider implements NewsFeedProviderInterface
{
    public function fetch(string $query): array
    {
        $url = 'https://news.google.com/rss/search?q=' . urlencode($query) . '&hl=ru&gl=RU&ceid=RU:ru';

        return $this->fetchRss('googlenews', $url);
    }
}

