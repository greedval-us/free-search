<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;

final class GoogleNewsRssProvider extends AbstractRssNewsFeedProvider implements NewsFeedProviderInterface
{
    public function fetch(string $query): array
    {
        $template = (string) config(
            'osint.news_media_intel.rss.google.url_template',
            'https://news.google.com/rss/search?q={query}&hl={hl}&gl={gl}&ceid={ceid}'
        );
        $hl = (string) config('osint.news_media_intel.rss.google.hl', 'ru');
        $gl = (string) config('osint.news_media_intel.rss.google.gl', 'RU');
        $ceid = (string) config('osint.news_media_intel.rss.google.ceid', 'RU:ru');
        $url = $this->buildUrlFromTemplate($template, $query, [
            'hl' => $hl,
            'gl' => $gl,
            'ceid' => $ceid,
        ]);

        return $this->fetchRss('googlenews', $url);
    }
}
