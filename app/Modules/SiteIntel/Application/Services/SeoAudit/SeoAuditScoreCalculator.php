<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditScoreCalculator
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, int>  $headings
     * @param  array<string, mixed>  $indexability
     * @param  array<string, mixed>  $robots
     * @param  array<string, mixed>  $sitemap
     * @param  array<string, mixed>  $performance
     * @param  array<string, mixed>  $security
     * @param  array<string, mixed>  $crawl
     * @param  array<string, mixed>  $sitemapAudit
     * @return array<string, mixed>
     */
    public function calculate(
        array $meta,
        array $headings,
        array $indexability,
        array $robots,
        array $sitemap,
        array $performance,
        array $security,
        array $crawl,
        array $sitemapAudit,
    ): array {
        $score = 100;
        $signals = [];

        if (($meta['titleLength'] ?? 0) < 15 || ($meta['titleLength'] ?? 0) > 65) {
            $score -= 10;
            $signals[] = 'title_length_out_of_range';
        }

        if (($meta['descriptionLength'] ?? 0) < 50 || ($meta['descriptionLength'] ?? 0) > 170) {
            $score -= 8;
            $signals[] = 'description_length_out_of_range';
        }

        if (($headings['h1'] ?? 0) !== 1) {
            $score -= 10;
            $signals[] = 'invalid_h1_count';
        }

        if (($indexability['indexable'] ?? false) !== true) {
            $score -= 20;
            $signals[] = 'not_indexable';
        }

        if (($robots['available'] ?? false) !== true) {
            $score -= 8;
            $signals[] = 'robots_missing';
        }

        if (($sitemap['available'] ?? false) !== true) {
            $score -= 8;
            $signals[] = 'sitemap_missing';
        }

        if (($performance['ttfbMsApprox'] ?? 0) > 1200) {
            $score -= 8;
            $signals[] = 'slow_response';
        }

        if (($performance['pageSizeKb'] ?? 0) > 1500) {
            $score -= 6;
            $signals[] = 'heavy_page';
        }

        if (($security['https'] ?? false) !== true) {
            $score -= 15;
            $signals[] = 'https_missing';
        }

        if (($security['mixedContent'] ?? false) === true) {
            $score -= 7;
            $signals[] = 'mixed_content';
        }

        $duplicateTitles = is_array($crawl['duplicates']['titles'] ?? null) ? $crawl['duplicates']['titles'] : [];
        if ($duplicateTitles !== []) {
            $score -= 5;
            $signals[] = 'duplicate_titles';
        }

        $canonicalMissing = is_array($crawl['canonicalAudit']['missing'] ?? null) ? $crawl['canonicalAudit']['missing'] : [];
        if ($canonicalMissing !== []) {
            $score -= 5;
            $signals[] = 'missing_canonical';
        }

        $hreflangConflicts = is_array($crawl['hreflangAudit']['duplicateLangTags'] ?? null) ? $crawl['hreflangAudit']['duplicateLangTags'] : [];
        if ($hreflangConflicts !== []) {
            $score -= 4;
            $signals[] = 'hreflang_conflicts';
        }

        $sitemapNon200 = is_array($sitemapAudit['non200'] ?? null) ? $sitemapAudit['non200'] : [];
        if ($sitemapNon200 !== []) {
            $score -= 6;
            $signals[] = 'sitemap_non_200_urls';
        }

        $score = max(0, min(100, $score));
        $level = $score >= 80 ? 'high' : ($score >= 55 ? 'medium' : 'low');

        return [
            'value' => $score,
            'level' => $level,
            'signals' => $signals,
        ];
    }
}
