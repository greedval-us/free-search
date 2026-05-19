<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Providers;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;

final class BingNewsRssProvider extends AbstractRssNewsFeedProvider implements NewsFeedProviderInterface
{
    public function fetch(string $query): array
    {
        $url = 'https://www.bing.com/search?format=rss&q=' . urlencode($query);

        return $this->fetchRss('bing', $url);
    }
}

