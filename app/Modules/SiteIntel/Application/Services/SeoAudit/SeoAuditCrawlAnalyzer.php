<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

use App\Modules\SiteIntel\Application\Contracts\SeoAuditHttpFetcherInterface;

final class SeoAuditCrawlAnalyzer
{
    public function __construct(
        private readonly SeoAuditHttpFetcherInterface $httpFetcher,
        private readonly SeoAuditContentExtractor $contentExtractor,
        private readonly SeoAuditTechnicalSignalsResolver $technicalSignalsResolver,
        private readonly SeoAuditCrawlReportBuilder $reportBuilder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $startUrl, int $limit): array
    {
        $host = (string) parse_url($startUrl, PHP_URL_HOST);
        if ($host === '') {
            return $this->reportBuilder->emptyResult($limit);
        }

        $visited = [];
        $queue = [$startUrl];
        $pages = [];
        $pageLinks = [];

        while ($queue !== [] && count($pages) < $limit) {
            $url = array_shift($queue);
            if (!is_string($url) || isset($visited[$url])) {
                continue;
            }

            $visited[$url] = true;
            $fetch = $this->httpFetcher->fetch($url);
            $finalUrl = (string) ($fetch['url'] ?? $url);
            $finalHost = (string) parse_url($finalUrl, PHP_URL_HOST);
            $html = (string) ($fetch['body'] ?? '');
            $headers = is_array($fetch['headers'] ?? null) ? $fetch['headers'] : [];

            $meta = $this->contentExtractor->extractMeta($html);
            $headings = $this->contentExtractor->extractHeadings($html);
            $indexability = $this->technicalSignalsResolver->resolveIndexability($headers, $meta);
            $hreflang = $this->contentExtractor->extractHreflang($html);

            $pages[] = [
                'url' => $finalUrl,
                'status' => (int) ($fetch['status'] ?? 0),
                'title' => (string) ($meta['title'] ?? ''),
                'description' => (string) ($meta['description'] ?? ''),
                'titleLength' => (int) ($meta['titleLength'] ?? 0),
                'descriptionLength' => (int) ($meta['descriptionLength'] ?? 0),
                'h1Count' => (int) ($headings['h1'] ?? 0),
                'canonical' => (string) ($meta['canonical'] ?? ''),
                'indexable' => (bool) ($indexability['indexable'] ?? false),
                'hreflang' => $hreflang,
            ];

            if ($finalHost === '' || mb_strtolower($finalHost) !== mb_strtolower($host)) {
                continue;
            }

            $links = $this->contentExtractor->extractCrawlableLinks($html, $finalUrl, $host);
            $pageLinks[$finalUrl] = $links;
            foreach ($links as $link) {
                if (!isset($visited[$link]) && !in_array($link, $queue, true) && count($queue) + count($pages) < ($limit * 4)) {
                    $queue[] = $link;
                }
            }
        }

        return [
            'limit' => $limit,
            'scanned' => count($pages),
            'pages' => $pages,
            'linkGraph' => $this->reportBuilder->buildLinkGraph($pages, $pageLinks),
            'duplicates' => $this->reportBuilder->buildDuplicates($pages),
            'canonicalAudit' => $this->reportBuilder->buildCanonicalAudit($pages, $host),
            'hreflangAudit' => $this->reportBuilder->buildHreflangAudit($pages),
        ];
    }
}
