<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;

final class GoogleNewsRssProvider extends AbstractRssNewsFeedProvider implements NewsFeedProviderInterface
{
    public function fetch(string $query): array
    {
        $template = $this->config->googleRssUrlTemplate();
        $hl = $this->config->googleRssHl();
        $gl = $this->config->googleRssGl();
        $ceid = $this->config->googleRssCeid();
        $url = $this->buildUrlFromTemplate($template, $query, [
            'hl' => $hl,
            'gl' => $gl,
            'ceid' => $ceid,
        ]);

        return $this->fetchRss('googlenews', $url);
    }
}
