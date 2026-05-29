<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Enums\NewsFeedSource;

final class BingNewsRssProvider extends AbstractTemplateRssNewsFeedProvider
{
    protected function sourceKey(): string
    {
        return NewsFeedSource::Bing->value;
    }

    protected function urlTemplate(): string
    {
        return $this->config->bingRssUrlTemplate();
    }
}
