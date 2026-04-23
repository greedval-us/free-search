<?php

namespace App\Modules\Fio\Infrastructure\Parsers;

use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;

final class FioGoogleNewsRssResultParser
{
    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function parse(string $xml): array
    {
        if (trim($xml) === '') {
            return [];
        }

        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml);
        libxml_clear_errors();

        if ($feed === false) {
            return [];
        }

        $results = $this->parseRssItems($feed);
        if (count($results) > 0) {
            return $results;
        }

        return $this->parseAtomEntries($feed);
    }

    /**
     * @param \SimpleXMLElement $feed
     * @return array<int, PublicSearchEntryDTO>
     */
    private function parseRssItems(\SimpleXMLElement $feed): array
    {
        if (!isset($feed->channel->item)) {
            return [];
        }

        $results = [];

        foreach ($feed->channel->item as $item) {
            $title = trim((string) ($item->title ?? ''));
            $url = trim((string) ($item->link ?? ''));
            $snippet = $this->normalizeSnippet((string) ($item->description ?? ''));

            if ($title === '' || $url === '') {
                continue;
            }

            $results[] = new PublicSearchEntryDTO(
                title: html_entity_decode($title, ENT_QUOTES | ENT_HTML5),
                snippet: $snippet,
                url: $url,
                domain: $this->extractDomain($url),
                source: 'googlenews',
            );

            if (count($results) >= 60) {
                break;
            }
        }

        return $results;
    }

    /**
     * @param \SimpleXMLElement $feed
     * @return array<int, PublicSearchEntryDTO>
     */
    private function parseAtomEntries(\SimpleXMLElement $feed): array
    {
        $entries = $feed->entry ?? null;
        if ($entries === null) {
            return [];
        }

        $results = [];

        foreach ($entries as $entry) {
            $title = trim((string) ($entry->title ?? ''));
            $summary = $this->normalizeSnippet((string) ($entry->summary ?? ''));
            $url = '';

            if (isset($entry->link)) {
                foreach ($entry->link as $link) {
                    $href = (string) ($link->attributes()->href ?? '');
                    if ($href !== '') {
                        $url = trim($href);
                        break;
                    }
                }
            }

            if ($title === '' || $url === '') {
                continue;
            }

            $results[] = new PublicSearchEntryDTO(
                title: html_entity_decode($title, ENT_QUOTES | ENT_HTML5),
                snippet: $summary,
                url: $url,
                domain: $this->extractDomain($url),
                source: 'googlenews',
            );

            if (count($results) >= 60) {
                break;
            }
        }

        return $results;
    }

    private function extractDomain(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }

    private function normalizeSnippet(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
        $stripped = strip_tags($decoded);
        $normalized = preg_replace('/\s+/u', ' ', trim($stripped));

        return is_string($normalized) ? $normalized : '';
    }
}
