<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditSitemapUrlAuditor
{
    public function __construct(
        private readonly SeoAuditHttpFetcher $httpFetcher,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function audit(string $sitemapUrl, int $limit = 15): array
    {
        $fetch = $this->httpFetcher->fetch($sitemapUrl);
        $xml = (string) ($fetch['body'] ?? '');

        $urls = $this->extractLocUrls($xml, $limit);
        $checked = [];
        $non200 = [];

        foreach ($urls as $url) {
            $page = $this->httpFetcher->fetch($url);
            $status = (int) ($page['status'] ?? 0);
            $item = [
                'url' => $url,
                'status' => $status,
            ];
            $checked[] = $item;

            if ($status < 200 || $status >= 300) {
                $non200[] = $item;
            }
        }

        return [
            'source' => $sitemapUrl,
            'sampled' => count($checked),
            'non200' => $non200,
            'checked' => $checked,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractLocUrls(string $xml, int $limit): array
    {
        if ($xml === '') {
            return [];
        }

        preg_match_all('/<loc>(.*?)<\/loc>/is', $xml, $matches);
        $urls = [];
        foreach (($matches[1] ?? []) as $raw) {
            $url = trim(strip_tags(html_entity_decode((string) $raw)));
            if ($url === '' || !str_starts_with($url, 'http')) {
                continue;
            }
            $urls[] = $url;
            if (count($urls) >= $limit) {
                break;
            }
        }

        return array_values(array_unique($urls));
    }
}

