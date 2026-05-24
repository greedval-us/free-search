<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;

final class BingNewsRssProvider extends AbstractRssNewsFeedProvider implements NewsFeedProviderInterface
{
    public function fetch(string $query): array
    {
        $template = $this->config->bingRssUrlTemplate();
        $url = $this->buildUrlFromTemplate($template, $query);

        return $this->fetchRss('bing', $url);
    }
}
