<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;

final class BingNewsRssProvider extends AbstractRssNewsFeedProvider implements NewsFeedProviderInterface
{
    public function fetch(string $query): array
    {
        $template = (string) config(
            'osint.news_media_intel.rss.bing.url_template',
            'https://www.bing.com/search?format=rss&q={query}'
        );
        $url = $this->buildUrlFromTemplate($template, $query);

        return $this->fetchRss('bing', $url);
    }
}
