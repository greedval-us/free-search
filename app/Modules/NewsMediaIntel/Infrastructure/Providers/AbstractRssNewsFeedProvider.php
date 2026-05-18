<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Providers;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

abstract class AbstractRssNewsFeedProvider
{
    /**
     * @return array<int, NewsMentionDTO>
     */
    protected function fetchRss(string $source, string $url): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8',
            ])->timeout(15)->get($url);
        } catch (ConnectionException) {
            return [];
        }

        if (!$response->ok()) {
            return [];
        }

        libxml_use_internal_errors(true);
        $rss = simplexml_load_string((string) $response->body());
        if (!$rss instanceof SimpleXMLElement) {
            return [];
        }

        $items = [];
        foreach (($rss->channel->item ?? []) as $item) {
            $title = trim((string) ($item->title ?? ''));
            $snippet = trim(strip_tags((string) ($item->description ?? '')));
            $link = trim((string) ($item->link ?? ''));
            $publishedAt = trim((string) ($item->pubDate ?? ''));

            if ($title === '' || $link === '') {
                continue;
            }

            $items[] = new NewsMentionDTO(
                source: $source,
                title: $title,
                snippet: $snippet,
                link: $link,
                publishedAt: $publishedAt,
            );
        }

        return $items;
    }
}

