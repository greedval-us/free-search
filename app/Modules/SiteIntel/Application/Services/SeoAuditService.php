<?php

namespace App\Modules\SiteIntel\Application\Services;

use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditContentExtractor;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditCrawlAnalyzer;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditCrawlerInspector;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditHttpFetcher;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditRecommendationBuilder;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditScoreCalculator;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditSitemapUrlAuditor;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditTechnicalSignalsResolver;
use Carbon\Carbon;

final class SeoAuditService
{
    public function __construct(
        private readonly SeoAuditHttpFetcher $httpFetcher,
        private readonly SeoAuditContentExtractor $contentExtractor,
        private readonly SeoAuditTechnicalSignalsResolver $technicalSignalsResolver,
        private readonly SeoAuditCrawlerInspector $crawlerInspector,
        private readonly SeoAuditCrawlAnalyzer $crawlAnalyzer,
        private readonly SeoAuditSitemapUrlAuditor $sitemapUrlAuditor,
        private readonly SeoAuditScoreCalculator $scoreCalculator,
        private readonly SeoAuditRecommendationBuilder $recommendationBuilder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function audit(string $url, int $crawlLimit = 8): array
    {
        $final = $this->httpFetcher->fetch($url);
        $html = (string) ($final['body'] ?? '');
        $headers = is_array($final['headers'] ?? null) ? $final['headers'] : [];
        $finalUrl = (string) ($final['url'] ?? $url);

        $host = (string) parse_url($finalUrl, PHP_URL_HOST);
        $scheme = (string) parse_url($finalUrl, PHP_URL_SCHEME);
        $origin = sprintf('%s://%s', $scheme !== '' ? $scheme : 'https', $host);

        $meta = $this->contentExtractor->extractMeta($html);
        $headings = $this->contentExtractor->extractHeadings($html);
        $links = $this->contentExtractor->extractLinks($html, $host);

        $indexability = $this->technicalSignalsResolver->resolveIndexability($headers, $meta);
        $performance = $this->technicalSignalsResolver->estimatePerformance($final);
        $security = $this->technicalSignalsResolver->resolveSecurity($finalUrl, $html, $headers);

        $robots = $this->crawlerInspector->checkRobotsTxt($origin);
        $sitemap = $this->crawlerInspector->checkSitemap($origin, (string) ($robots['sitemapFromRobots'] ?? ''));
        $crawl = $this->crawlAnalyzer->analyze($finalUrl, $crawlLimit);
        $sitemapAudit = ($sitemap['available'] ?? false) === true
            ? $this->sitemapUrlAuditor->audit((string) ($sitemap['url'] ?? ''), min(15, $crawlLimit + 2))
            : [
                'source' => (string) ($sitemap['url'] ?? ''),
                'sampled' => 0,
                'non200' => [],
                'checked' => [],
            ];

        $score = $this->scoreCalculator->calculate(
            $meta,
            $headings,
            $indexability,
            $robots,
            $sitemap,
            $performance,
            $security,
            $crawl,
            $sitemapAudit,
        );
        $recommendations = $this->recommendationBuilder->build(
            $meta,
            $headings,
            $indexability,
            $robots,
            $sitemap,
            $performance,
            $security,
            $crawl,
            $sitemapAudit,
        );

        return [
            'target' => [
                'input' => $url,
                'finalUrl' => $finalUrl,
                'host' => $host,
            ],
            'checkedAt' => Carbon::now()->toIso8601String(),
            'status' => [
                'httpCode' => (int) ($final['status'] ?? 0),
                'responseTimeMs' => (int) ($final['responseTimeMs'] ?? 0),
            ],
            'meta' => $meta,
            'headings' => $headings,
            'links' => $links,
            'indexability' => $indexability,
            'robots' => $robots,
            'sitemap' => $sitemap,
            'performance' => $performance,
            'security' => $security,
            'crawl' => $crawl,
            'sitemapAudit' => $sitemapAudit,
            'score' => $score,
            'recommendations' => $recommendations,
        ];
    }
}
