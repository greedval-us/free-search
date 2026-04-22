<?php

namespace App\Modules\Fio\Infrastructure\Parsers;

use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;

final class FioBingRssResultParser
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

        if ($feed === false || !isset($feed->channel->item)) {
            return [];
        }

        $results = [];

        foreach ($feed->channel->item as $item) {
            $title = trim((string) ($item->title ?? ''));
            $url = trim((string) ($item->link ?? ''));
            $snippet = trim((string) ($item->description ?? ''));

            if ($title === '' || $url === '') {
                continue;
            }

            $results[] = new PublicSearchEntryDTO(
                title: html_entity_decode($title, ENT_QUOTES | ENT_HTML5),
                snippet: html_entity_decode($snippet, ENT_QUOTES | ENT_HTML5),
                url: $url,
                domain: $this->extractDomain($url),
                source: 'bing',
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
}
