<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Support\NewsFeedSources;

final class BingNewsRssProvider extends AbstractTemplateRssNewsFeedProvider
{
    protected function sourceKey(): string
    {
        return NewsFeedSources::BING;
    }

    protected function urlTemplate(): string
    {
        return $this->config->bingRssUrlTemplate();
    }
}
