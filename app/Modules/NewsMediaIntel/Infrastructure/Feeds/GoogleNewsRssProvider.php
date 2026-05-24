<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Support\NewsFeedSources;

final class GoogleNewsRssProvider extends AbstractTemplateRssNewsFeedProvider
{
    protected function sourceKey(): string
    {
        return NewsFeedSources::GOOGLE_NEWS;
    }

    protected function urlTemplate(): string
    {
        return $this->config->googleRssUrlTemplate();
    }

    /**
     * @return array<string, string>
     */
    protected function templateTokens(): array
    {
        $hl = $this->config->googleRssHl();
        $gl = $this->config->googleRssGl();
        $ceid = $this->config->googleRssCeid();

        return [
            'hl' => $hl,
            'gl' => $gl,
            'ceid' => $ceid,
        ];
    }
}
