<?php

namespace Tests\Unit;

use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteRiskScoreCalculator;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditScoreCalculator;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditScoringProfilePolicy;
use App\Modules\SiteIntel\Application\Services\SeoAudit\SeoAuditTechnicalThresholds;
use App\Modules\SiteIntel\Application\Services\SiteHealth\SiteHealthScoreCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SiteIntelScoringTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_domain_risk_score_uses_centralized_signal_points(): void
    {
        $result = (new DomainLiteRiskScoreCalculator)->calculate([], []);

        $this->assertSame(83, $result['score']);
        $this->assertSame('high', $result['level']);
        $this->assertSame(25, $result['breakdown'][0]['points']);
        $this->assertContains('whois_unavailable', $result['signals']);
    }

    public function test_domain_risk_score_keeps_healthy_domain_at_zero(): void
    {
        Carbon::setTestNow('2026-09-07 12:00:00');

        $result = (new DomainLiteRiskScoreCalculator)->calculate([
            'a' => ['203.0.113.10'],
            'aaaa' => [],
            'ns' => ['ns1.example.test'],
            'mx' => ['mail.example.test'],
            'emailSecurity' => [
                'hasSpf' => true,
                'spfPolicy' => ['allQualifier' => '-', 'includeCount' => 0],
                'hasDmarc' => true,
                'dmarcPolicy' => ['policy' => 'reject', 'percentage' => 100],
            ],
            'dnssec' => ['enabled' => true],
        ], [
            'available' => true,
            'createdAt' => '2024-01-01T00:00:00+00:00',
            'expiresAt' => '2027-09-07T00:00:00+00:00',
        ]);

        $this->assertSame(0, $result['score']);
        $this->assertSame('low', $result['level']);
        $this->assertSame([], $result['signals']);
    }

    public function test_site_health_score_applies_shared_signal_penalties(): void
    {
        $result = (new SiteHealthScoreCalculator)->calculate(
            dns: [],
            http: ['finalStatus' => 500, 'finalUrl' => 'http://example.test', 'totalRedirects' => 4],
            ssl: ['available' => false],
            securityHeaders: ['content-security-policy' => ['present' => false]],
        );

        $this->assertSame(14, $result['value']);
        $this->assertSame('low', $result['level']);
        $this->assertSame([
            'no_dns_records',
            'http_error_status',
            'final_url_not_https',
            'too_many_redirects',
            'missing_content_security_policy',
        ], $result['signals']);
    }

    public function test_seo_score_preserves_profile_weighting_and_bonus(): void
    {
        $calculator = new SeoAuditScoreCalculator(
            new SeoAuditTechnicalThresholds,
            new SeoAuditScoringProfilePolicy,
        );

        $result = $calculator->calculate(
            meta: ['titleLength' => 0, 'descriptionLength' => 100],
            headings: ['h1' => 1],
            indexability: ['indexable' => true],
            robots: ['available' => true],
            sitemap: ['available' => true],
            performance: [
                'ttfbMsApprox' => 1500,
                'pageSizeKb' => 1600,
                'renderBlocking' => ['total' => 7],
            ],
            security: ['https' => true, 'mixedContent' => false],
            mobileFriendly: ['isResponsive' => true],
            pagination: ['isPaginated' => false],
            soft404: ['detected' => false],
            quality: [
                'anchors' => ['empty' => 0],
                'accessibility' => ['imagesWithoutAlt' => 0],
                'content' => ['thinContent' => false, 'lowTextRatio' => true],
                'linkGraph' => ['orphanRisk' => false],
                'htmlValidation' => ['issueCount' => 0],
            ],
            international: ['missingReciprocal' => [], 'missingXDefault' => []],
            crawlBudget: ['botHits' => 1, 'statusBuckets' => ['5xx' => 1]],
            profile: ['key' => 'media-platform'],
            crawl: [
                'duplicates' => ['titles' => []],
                'canonicalAudit' => ['missing' => []],
                'hreflangAudit' => ['duplicateLangTags' => []],
            ],
            sitemapAudit: ['non200' => []],
        );

        $this->assertSame(82, $result['value']);
        $this->assertSame('high', $result['level']);
        $this->assertContains('render_blocking_resources', $result['signals']);
    }
}
