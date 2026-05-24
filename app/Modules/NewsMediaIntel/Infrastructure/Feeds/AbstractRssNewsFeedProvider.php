<?php

namespace App\Modules\NewsMediaIntel\Infrastructure\Feeds;

use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

abstract class AbstractRssNewsFeedProvider
{
    public function __construct(
        protected readonly NewsMediaIntelConfig $config,
    ) {
    }

    /**
     * @param array<string, string> $tokens
     */
    protected function buildUrlFromTemplate(string $template, string $query, array $tokens = []): string
    {
        $replacements = ['{query}' => urlencode($query)];

        foreach ($tokens as $key => $value) {
            $replacements['{' . $key . '}'] = urlencode($value);
        }

        return strtr($template, $replacements);
    }

    /**
     * @return array<int, NewsMentionDTO>
     */
    protected function fetchRss(string $source, string $url): array
    {
        $acceptHeader = $this->config->rssAcceptHeader();
        $timeoutSeconds = $this->config->rssTimeoutSeconds();

        try {
            $response = Http::withHeaders([
                'Accept' => $acceptHeader,
            ])->timeout($timeoutSeconds)->get($url);
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
            $publishedAt = $this->extractPublishedAt($item);

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

    private function extractPublishedAt(SimpleXMLElement $item): string
    {
        $candidates = [
            trim((string) ($item->pubDate ?? '')),
            trim((string) ($item->published ?? '')),
            trim((string) ($item->updated ?? '')),
        ];

        foreach ($item->getNamespaces(true) as $namespaceUri) {
            $children = $item->children($namespaceUri);
            if (!$children instanceof SimpleXMLElement) {
                continue;
            }

            $candidates[] = trim((string) ($children->date ?? ''));
            $candidates[] = trim((string) ($children->published ?? ''));
            $candidates[] = trim((string) ($children->updated ?? ''));
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }
}
