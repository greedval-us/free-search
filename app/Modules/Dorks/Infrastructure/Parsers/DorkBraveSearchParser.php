<?php

namespace App\Modules\Dorks\Infrastructure\Parsers;

use App\Modules\Dorks\Domain\DTO\DorkResultItemDTO;

final class DorkBraveSearchParser
{
    /**
     * @return array<int, DorkResultItemDTO>
     */
    public function parse(string $html, string $goal, string $dork, int $limit): array
    {
        if (trim($html) === '') {
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
        $rows = $xpath->query('//div[contains(@class,"snippet") or contains(@class,"result")]');

        if ($rows === false || $rows->length === 0) {
            return [];
        }

        $results = [];

        foreach ($rows as $row) {
            $titleNode = $xpath->query('.//a[@href]', $row)?->item(0);
            if (!$titleNode instanceof \DOMElement) {
                continue;
            }

            $title = trim($titleNode->textContent);
            $url = $this->resolveUrl(trim($titleNode->getAttribute('href')));
            $snippetNode = $xpath->query('.//p | .//div[contains(@class,"snippet")]', $row)?->item(0);
            $snippet = $snippetNode instanceof \DOMNode ? trim($snippetNode->textContent) : '';

            if ($title === '' || $url === '') {
                continue;
            }

            $results[] = new DorkResultItemDTO(
                source: 'brave',
                goal: $goal,
                dork: $dork,
                title: html_entity_decode($title, ENT_QUOTES | ENT_HTML5),
                snippet: html_entity_decode($snippet, ENT_QUOTES | ENT_HTML5),
                url: $url,
                domain: $this->extractDomain($url),
            );

            if (count($results) >= $limit) {
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

        if (str_starts_with($href, '/')) {
            return '';
        }

        if (!str_starts_with($href, 'http://') && !str_starts_with($href, 'https://')) {
            return '';
        }

        return $href;
    }

    private function extractDomain(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }
}

