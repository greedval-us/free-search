<?php

namespace App\Modules\Dorks\Infrastructure\Parsers;

use App\Modules\Dorks\Domain\DTO\DorkResultItemDTO;

final class DorkRedditRssParser
{
    /**
     * @return array<int, DorkResultItemDTO>
     */
    public function parse(string $xml, string $goal, string $dork, int $limit): array
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

        $results = [];
        $entries = $this->entries($feed);

        foreach ($entries as $entry) {
            $title = $this->extractTitle($entry);
            $url = $this->extractUrl($entry);
            $snippet = $this->extractSnippet($entry);

            if ($title === '' || $url === '') {
                continue;
            }

            $results[] = new DorkResultItemDTO(
                source: 'reddit',
                goal: $goal,
                dork: $dork,
                title: html_entity_decode($title, ENT_QUOTES | ENT_HTML5),
                snippet: $snippet,
                url: $url,
                domain: $this->extractDomain($url),
            );

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return array<int, \SimpleXMLElement>
     */
    private function entries(\SimpleXMLElement $feed): array
    {
        if (isset($feed->channel->item)) {
            return iterator_to_array($feed->channel->item);
        }

        if (isset($feed->entry)) {
            return iterator_to_array($feed->entry);
        }

        return [];
    }

    private function extractTitle(\SimpleXMLElement $entry): string
    {
        return trim((string) ($entry->title ?? ''));
    }

    private function extractUrl(\SimpleXMLElement $entry): string
    {
        $rssLink = trim((string) ($entry->link ?? ''));
        if ($rssLink !== '') {
            return $rssLink;
        }

        $attributes = $entry->link?->attributes();
        $href = trim((string) ($attributes?->href ?? ''));

        return $href;
    }

    private function extractSnippet(\SimpleXMLElement $entry): string
    {
        $description = (string) ($entry->description ?? '');
        if ($description === '' && isset($entry->summary)) {
            $description = (string) $entry->summary;
        }

        return $this->normalizeSnippet($description);
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
