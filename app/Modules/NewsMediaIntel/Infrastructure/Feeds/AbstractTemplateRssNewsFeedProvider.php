<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedProviderInterface;

abstract class AbstractTemplateRssNewsFeedProvider extends AbstractRssNewsFeedProvider implements NewsFeedProviderInterface
{
    public function key(): string
    {
        return $this->sourceKey();
    }

    /**
     * @return array<string, string>
     */
    protected function templateTokens(): array
    {
        return [];
    }

    abstract protected function sourceKey(): string;

    abstract protected function urlTemplate(): string;

    public function fetch(string $query): array
    {
        $url = $this->buildUrlFromTemplate(
            $this->urlTemplate(),
            $query,
            $this->templateTokens()
        );

        return $this->fetchRss($this->sourceKey(), $url);
    }
}
