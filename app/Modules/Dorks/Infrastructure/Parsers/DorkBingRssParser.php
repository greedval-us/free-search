<?php

namespace App\Modules\Dorks\Infrastructure\Parsers;

use App\Modules\Dorks\Domain\DTO\DorkResultItemDTO;

final class DorkBingRssParser
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

        if ($feed === false || !isset($feed->channel->item)) {
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

            $results[] = new DorkResultItemDTO(
                source: 'bing',
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

