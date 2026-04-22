<?php

namespace App\Modules\Fio\Infrastructure\Parsers;

use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;

final class FioDuckDuckGoResultParser
{
    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function parse(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();

        if ($loaded === false) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query('//div[contains(@class,"result")]');
        if ($rows === false || $rows->length === 0) {
            return [];
        }

        $results = [];

        foreach ($rows as $row) {
            $titleNode = $xpath->query('.//a[contains(@class,"result__a")]', $row)?->item(0);
            if (!$titleNode instanceof \DOMElement) {
                continue;
            }

            $snippetNode = $xpath->query('.//*[contains(@class,"result__snippet")]', $row)?->item(0);
            $title = trim($titleNode->textContent);
            $href = trim($titleNode->getAttribute('href'));
            $url = $this->resolveUrl($href);
            $snippet = $snippetNode instanceof \DOMNode ? trim($snippetNode->textContent) : '';

            if ($title === '' || $url === '') {
                continue;
            }

            $results[] = new PublicSearchEntryDTO(
                title: html_entity_decode($title, ENT_QUOTES | ENT_HTML5),
                snippet: html_entity_decode($snippet, ENT_QUOTES | ENT_HTML5),
                url: $url,
                domain: $this->extractDomain($url),
                source: 'duckduckgo',
            );

            if (count($results) >= 30) {
                break;
            }
        }

        return $results;
    }

    private function resolveUrl(string $href): string
    {
        if ($href === '') {
            return '';
        }

        if (str_starts_with($href, '//')) {
            return 'https:' . $href;
        }

        if (!str_starts_with($href, 'http://') && !str_starts_with($href, 'https://')) {
            return '';
        }

        $parsed = parse_url($href);
        $query = isset($parsed['query']) ? (string) $parsed['query'] : '';
        parse_str($query, $params);
        $redirect = $params['uddg'] ?? null;

        if (is_string($redirect) && $redirect !== '') {
            return urldecode($redirect);
        }

        return $href;
    }

    private function extractDomain(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }
}
