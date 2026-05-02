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
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditQualityAnalyzer;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditInternationalAnalyzer;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditCrawlBudgetAnalyzer;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditProfileResolver;
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
        private readonly SeoAuditQualityAnalyzer $qualityAnalyzer,
        private readonly SeoAuditInternationalAnalyzer $internationalAnalyzer,
        private readonly SeoAuditCrawlBudgetAnalyzer $crawlBudgetAnalyzer,
        private readonly SeoAuditProfileResolver $profileResolver,
        private readonly SeoAuditScoreCalculator $scoreCalculator,
        private readonly SeoAuditRecommendationBuilder $recommendationBuilder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function audit(string $url, int $crawlLimit = 8, ?string $platformType = null): array
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
        $mobileFriendly = $this->technicalSignalsResolver->resolveMobileFriendly($html);
        $pagination = $this->technicalSignalsResolver->resolvePaginationSignals($html);
        $soft404 = $this->technicalSignalsResolver->detectSoft404($html, (int) ($final['status'] ?? 0));
        $quality = $this->qualityAnalyzer->analyze($html, $finalUrl);

        $robots = $this->crawlerInspector->checkRobotsTxt($origin);
        $sitemap = $this->crawlerInspector->checkSitemap($origin, (string) ($robots['sitemapFromRobots'] ?? ''));
        $crawl = $this->crawlAnalyzer->analyze($finalUrl, $crawlLimit);
        $international = $this->internationalAnalyzer->analyze($crawl);
        $crawlBudget = $this->crawlBudgetAnalyzer->analyze($host);
        $profile = $this->profileResolver->resolve($performance, $quality, $crawl, $platformType);
        $sitemapAudit = $this->resolveSitemapAudit($sitemap, $crawlLimit);

        $score = $this->scoreCalculator->calculate(
            $meta,
            $headings,
            $indexability,
            $robots,
            $sitemap,
            $performance,
            $security,
            $mobileFriendly,
            $pagination,
            $soft404,
            $quality,
            $international,
            $crawlBudget,
            $profile,
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
            $mobileFriendly,
            $pagination,
            $soft404,
            $quality,
            $international,
            $crawlBudget,
            $profile,
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
            'mobileFriendly' => $mobileFriendly,
            'pagination' => $pagination,
            'soft404' => $soft404,
            'quality' => $quality,
            'international' => $international,
            'crawlBudget' => $crawlBudget,
            'profile' => $profile,
            'crawl' => $crawl,
            'sitemapAudit' => $sitemapAudit,
            'score' => $score,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @param  array<string, mixed>  $sitemap
     * @return array<string, mixed>
     */
    private function resolveSitemapAudit(array $sitemap, int $crawlLimit): array
    {
        if (($sitemap['available'] ?? false) === true) {
            return $this->sitemapUrlAuditor->audit((string) ($sitemap['url'] ?? ''), min(15, $crawlLimit + 2));
        }

        return [
            'source' => (string) ($sitemap['url'] ?? ''),
            'sampled' => 0,
            'non200' => [],
            'checked' => [],
        ];
    }
}
