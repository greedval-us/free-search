<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use SimpleXMLElement;

final class FioMultiSourceResponseParser
{
    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function parseEngineResponse(string $body, string $source): array
    {
        $trimmed = ltrim($body);
        if ($trimmed !== '' && $trimmed[0] === '<' && (str_contains($trimmed, '<rss') || str_contains($trimmed, '<feed'))) {
            return $this->parseRssOrAtom($trimmed, $source);
        }

        return $this->parseHtml($body, $source);
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    private function parseRssOrAtom(string $xml, string $source): array
    {
        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml);
        if (!$feed instanceof SimpleXMLElement) {
            return [];
        }

        $entries = [];
        $items = $feed->channel->item ?? [];
        if ($items !== []) {
            foreach ($items as $item) {
                $title = trim((string) ($item->title ?? ''));
                $url = $this->normalizeResultUrl((string) ($item->link ?? ''));
                $snippet = trim(strip_tags((string) ($item->description ?? '')));
                if ($url === '' || $title === '') {
                    continue;
                }

                $entries[] = new PublicSearchEntryDTO($title, $snippet, $url, $this->extractDomain($url), $source);
            }

            return $entries;
        }

        foreach (($feed->entry ?? []) as $entry) {
            $title = trim((string) ($entry->title ?? ''));
            $url = '';
            if (isset($entry->link)) {
                $attrs = $entry->link->attributes();
                $url = (string) ($attrs['href'] ?? '');
            }
            $url = $this->normalizeResultUrl($url);
            $snippet = trim(strip_tags((string) ($entry->summary ?? $entry->content ?? '')));
            if ($url === '' || $title === '') {
                continue;
            }

            $entries[] = new PublicSearchEntryDTO($title, $snippet, $url, $this->extractDomain($url), $source);
        }

        return $entries;
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    private function parseHtml(string $html, string $source): array
    {
        preg_match_all('/<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);
        $entries = [];

        foreach ($matches as $match) {
            $rawUrl = html_entity_decode((string) ($match[2] ?? ''), ENT_QUOTES | ENT_HTML5);
            $url = $this->normalizeResultUrl($rawUrl);
            $title = trim(strip_tags(html_entity_decode((string) ($match[3] ?? ''), ENT_QUOTES | ENT_HTML5)));

            if ($url === '' || $title === '' || !$this->isHttpUrl($url)) {
                continue;
            }

            $entries[] = new PublicSearchEntryDTO($title, '', $url, $this->extractDomain($url), $source);
        }

        return $entries;
    }

    private function normalizeResultUrl(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/l/?kh=') && str_contains($url, 'uddg=')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $decoded = urldecode((string) ($query['uddg'] ?? ''));
            if ($decoded !== '') {
                return $decoded;
            }
        }

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return '';
        }

        return $url;
    }

    private function isHttpUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    private function extractDomain(string $url): ?string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return $host !== '' ? $host : null;
    }
}
