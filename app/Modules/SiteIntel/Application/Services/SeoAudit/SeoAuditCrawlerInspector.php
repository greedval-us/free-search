<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditCrawlerInspector
{
    public function __construct(
        private readonly SeoAuditHttpFetcher $httpFetcher,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function checkRobotsTxt(string $origin): array
    {
        $url = rtrim($origin, '/') . '/robots.txt';
        $response = $this->httpFetcher->fetch($url);
        $content = $response['body'];

        $sitemapFromRobots = '';
        if ($content !== '' && preg_match('/^sitemap:\s*(.+)$/im', $content, $match) === 1) {
            $sitemapFromRobots = trim((string) ($match[1] ?? ''));
        }

        return [
            'url' => $url,
            'available' => $response['status'] >= 200 && $response['status'] < 400 && $content !== '',
            'status' => $response['status'],
            'sitemapFromRobots' => $sitemapFromRobots,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function checkSitemap(string $origin, string $sitemapFromRobots): array
    {
        $candidate = $sitemapFromRobots !== '' ? $sitemapFromRobots : rtrim($origin, '/') . '/sitemap.xml';
        $response = $this->httpFetcher->fetch($candidate);

        $urlCount = 0;
        if ($response['body'] !== '') {
            $urlCount = preg_match_all('/<url>/i', $response['body']) ?: 0;
            if ($urlCount === 0) {
                $urlCount = preg_match_all('/<sitemap>/i', $response['body']) ?: 0;
            }
        }

        return [
            'url' => $candidate,
            'available' => $response['status'] >= 200 && $response['status'] < 400 && $response['body'] !== '',
            'status' => $response['status'],
            'entries' => $urlCount,
        ];
    }
}

