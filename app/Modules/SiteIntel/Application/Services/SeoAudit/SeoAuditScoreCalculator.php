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
     * @param  array<string, mixed>  $mobileFriendly
     * @param  array<string, mixed>  $pagination
     * @param  array<string, mixed>  $soft404
     * @param  array<string, mixed>  $quality
     * @param  array<string, mixed>  $international
     * @param  array<string, mixed>  $crawlBudget
     * @param  array<string, mixed>  $profile
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
        array $mobileFriendly,
        array $pagination,
        array $soft404,
        array $quality,
        array $international,
        array $crawlBudget,
        array $profile,
        array $crawl,
        array $sitemapAudit,
    ): array {
        $score = 100;
        $signals = [];
        $profileKey = (string) ($profile['key'] ?? 'generic');
        $weights = $this->profileWeights($profileKey);

        if (($meta['titleLength'] ?? 0) < 15 || ($meta['titleLength'] ?? 0) > 65) {
            $score -= (int) round(10 * $weights['meta']);
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
            $score -= (int) round(8 * $weights['performance']);
            $signals[] = 'slow_response';
        }

        if (($performance['pageSizeKb'] ?? 0) > 1500) {
            $score -= (int) round(6 * $weights['page_size']);
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
        if (($mobileFriendly['isResponsive'] ?? false) !== true) {
            $score -= 8;
            $signals[] = 'missing_mobile_viewport';
        }
        if (($soft404['detected'] ?? false) === true) {
            $score -= 15;
            $signals[] = 'soft_404_detected';
        }
        if ((int) ($performance['renderBlocking']['total'] ?? 0) > 6) {
            $score -= (int) round(5 * $weights['render_blocking']);
            $signals[] = 'render_blocking_resources';
        }
        if (($pagination['isPaginated'] ?? false) === true && !(($pagination['hasRelPrev'] ?? false) && ($pagination['hasRelNext'] ?? false))) {
            $score -= 3;
            $signals[] = 'pagination_signals_incomplete';
        }
        if ((int) ($quality['anchors']['empty'] ?? 0) > 0) {
            $score -= 4;
            $signals[] = 'empty_anchor_links';
        }
        if ((int) ($quality['accessibility']['imagesWithoutAlt'] ?? 0) > 0) {
            $score -= 5;
            $signals[] = 'images_missing_alt';
        }
        if (($quality['content']['thinContent'] ?? false) === true) {
            $score -= 5;
            $signals[] = 'thin_content_detected';
        }
        if (($quality['content']['lowTextRatio'] ?? false) === true) {
            $score -= (int) round(4 * $weights['text_ratio']);
            $signals[] = 'low_text_to_html_ratio';
        }
        if (($quality['linkGraph']['orphanRisk'] ?? false) === true) {
            $score -= 4;
            $signals[] = 'low_internal_linking';
        }
        if ((int) ($quality['htmlValidation']['issueCount'] ?? 0) > 0) {
            $score -= 3;
            $signals[] = 'html_structure_issues';
        }
        if ((int) count($international['missingReciprocal'] ?? []) > 0) {
            $score -= 4;
            $signals[] = 'hreflang_missing_reciprocal';
        }
        if ((int) count($international['missingXDefault'] ?? []) > 0) {
            $score -= 2;
            $signals[] = 'hreflang_missing_x_default';
        }
        if ((int) ($crawlBudget['botHits'] ?? 0) === 0) {
            $score -= (int) round(3 * $weights['crawl_budget_hits']);
            $signals[] = 'crawl_budget_no_bot_hits';
        }
        if ((int) ($crawlBudget['statusBuckets']['5xx'] ?? 0) > 0) {
            $score -= (int) round(5 * $weights['crawl_budget_5xx']);
            $signals[] = 'crawl_budget_bot_5xx';
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

        $score += $this->profileBaseBonus($profileKey);
        $score = max(0, min(100, $score));
        $levelThresholds = $this->profileLevelThresholds($profileKey);
        $level = $score >= $levelThresholds['high']
            ? 'high'
            : ($score >= $levelThresholds['medium'] ? 'medium' : 'low');

        return [
            'value' => $score,
            'level' => $level,
            'profile' => $profileKey,
            'signals' => $signals,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function profileWeights(string $profileKey): array
    {
        $default = [
            'meta' => 1.0,
            'performance' => 1.0,
            'render_blocking' => 1.0,
            'text_ratio' => 1.0,
            'page_size' => 1.0,
            'crawl_budget_hits' => 1.0,
            'crawl_budget_5xx' => 1.0,
        ];

        return match ($profileKey) {
            'media-platform' => [
                'meta' => 1.0,
                'performance' => 0.75,
                'render_blocking' => 0.45,
                'text_ratio' => 0.4,
                'page_size' => 0.5,
                'crawl_budget_hits' => 0.0,
                'crawl_budget_5xx' => 0.5,
            ],
            'storefront' => [
                'meta' => 1.0,
                'performance' => 0.9,
                'render_blocking' => 0.8,
                'text_ratio' => 0.8,
                'page_size' => 0.8,
                'crawl_budget_hits' => 0.6,
                'crawl_budget_5xx' => 0.9,
            ],
            'content-site' => [
                'meta' => 1.0,
                'performance' => 1.0,
                'render_blocking' => 0.9,
                'text_ratio' => 1.0,
                'page_size' => 1.0,
                'crawl_budget_hits' => 0.8,
                'crawl_budget_5xx' => 1.0,
            ],
            default => $default,
        };
    }

    private function profileBaseBonus(string $profileKey): int
    {
        return match ($profileKey) {
            'media-platform' => 8,
            'storefront' => 3,
            default => 0,
        };
    }

    /**
     * @return array{high: int, medium: int}
     */
    private function profileLevelThresholds(string $profileKey): array
    {
        return match ($profileKey) {
            'media-platform' => ['high' => 78, 'medium' => 50],
            'storefront' => ['high' => 80, 'medium' => 54],
            'content-site' => ['high' => 80, 'medium' => 55],
            default => ['high' => 80, 'medium' => 55],
        };
    }
}
