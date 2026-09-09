<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

use App\Modules\SiteIntel\Enums\SiteIntelScoreLevel;

final class SeoAuditScoreCalculator
{
    private const DEFAULT_PROFILE = 'generic';

    private const SCORE_MIN = 0;

    private const SCORE_MAX = 100;

    private const SIGNAL_PENALTIES = [
        'title_length_out_of_range' => 10,
        'description_length_out_of_range' => 8,
        'invalid_h1_count' => 10,
        'not_indexable' => 20,
        'robots_missing' => 8,
        'sitemap_missing' => 8,
        'slow_response' => 8,
        'heavy_page' => 6,
        'https_missing' => 15,
        'mixed_content' => 7,
        'missing_mobile_viewport' => 8,
        'soft_404_detected' => 15,
        'render_blocking_resources' => 5,
        'pagination_signals_incomplete' => 3,
        'empty_anchor_links' => 4,
        'images_missing_alt' => 5,
        'thin_content_detected' => 5,
        'low_text_to_html_ratio' => 4,
        'low_internal_linking' => 4,
        'html_structure_issues' => 3,
        'hreflang_missing_reciprocal' => 4,
        'hreflang_missing_x_default' => 2,
        'crawl_budget_no_bot_hits' => 3,
        'crawl_budget_bot_5xx' => 5,
        'duplicate_titles' => 5,
        'missing_canonical' => 5,
        'hreflang_conflicts' => 4,
        'sitemap_non_200_urls' => 6,
    ];

    public function __construct(
        private readonly SeoAuditTechnicalThresholds $thresholds,
        private readonly SeoAuditScoringProfilePolicy $profilePolicy,
    ) {}

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
        $score = self::SCORE_MAX;
        $signals = [];
        $profileKey = (string) ($profile['key'] ?? self::DEFAULT_PROFILE);
        $weights = $this->profilePolicy->weights($profileKey);
        $applySignal = function (string $key, float $weight = 1.0) use (&$score, &$signals): void {
            $score -= (int) round(self::SIGNAL_PENALTIES[$key] * $weight);
            $signals[] = $key;
        };

        if ($this->thresholds->isTitleOutOfRange((int) ($meta['titleLength'] ?? 0))) {
            $applySignal('title_length_out_of_range', $weights['meta']);
        }

        if ($this->thresholds->isDescriptionOutOfRange((int) ($meta['descriptionLength'] ?? 0))) {
            $applySignal('description_length_out_of_range');
        }

        if (($headings['h1'] ?? 0) !== 1) {
            $applySignal('invalid_h1_count');
        }

        if (($indexability['indexable'] ?? false) !== true) {
            $applySignal('not_indexable');
        }

        if (($robots['available'] ?? false) !== true) {
            $applySignal('robots_missing');
        }

        if (($sitemap['available'] ?? false) !== true) {
            $applySignal('sitemap_missing');
        }

        if ($this->thresholds->isSlowTtfb((int) ($performance['ttfbMsApprox'] ?? 0))) {
            $applySignal('slow_response', $weights['performance']);
        }

        if ($this->thresholds->isHeavyPage((int) ($performance['pageSizeKb'] ?? 0))) {
            $applySignal('heavy_page', $weights['page_size']);
        }

        if (($security['https'] ?? false) !== true) {
            $applySignal('https_missing');
        }

        if (($security['mixedContent'] ?? false) === true) {
            $applySignal('mixed_content');
        }
        if (($mobileFriendly['isResponsive'] ?? false) !== true) {
            $applySignal('missing_mobile_viewport');
        }
        if (($soft404['detected'] ?? false) === true) {
            $applySignal('soft_404_detected');
        }
        if ($this->thresholds->hasHighRenderBlocking((int) ($performance['renderBlocking']['total'] ?? 0))) {
            $applySignal('render_blocking_resources', $weights['render_blocking']);
        }
        if (($pagination['isPaginated'] ?? false) === true && ! (($pagination['hasRelPrev'] ?? false) && ($pagination['hasRelNext'] ?? false))) {
            $applySignal('pagination_signals_incomplete');
        }
        if ((int) ($quality['anchors']['empty'] ?? 0) > 0) {
            $applySignal('empty_anchor_links');
        }
        if ((int) ($quality['accessibility']['imagesWithoutAlt'] ?? 0) > 0) {
            $applySignal('images_missing_alt');
        }
        if (($quality['content']['thinContent'] ?? false) === true) {
            $applySignal('thin_content_detected');
        }
        if (($quality['content']['lowTextRatio'] ?? false) === true) {
            $applySignal('low_text_to_html_ratio', $weights['text_ratio']);
        }
        if (($quality['linkGraph']['orphanRisk'] ?? false) === true) {
            $applySignal('low_internal_linking');
        }
        if ((int) ($quality['htmlValidation']['issueCount'] ?? 0) > 0) {
            $applySignal('html_structure_issues');
        }
        if ((int) count($international['missingReciprocal'] ?? []) > 0) {
            $applySignal('hreflang_missing_reciprocal');
        }
        if ((int) count($international['missingXDefault'] ?? []) > 0) {
            $applySignal('hreflang_missing_x_default');
        }
        if ((int) ($crawlBudget['botHits'] ?? 0) === 0) {
            $applySignal('crawl_budget_no_bot_hits', $weights['crawl_budget_hits']);
        }
        if ((int) ($crawlBudget['statusBuckets']['5xx'] ?? 0) > 0) {
            $applySignal('crawl_budget_bot_5xx', $weights['crawl_budget_5xx']);
        }

        $duplicateTitles = is_array($crawl['duplicates']['titles'] ?? null) ? $crawl['duplicates']['titles'] : [];
        if ($duplicateTitles !== []) {
            $applySignal('duplicate_titles');
        }

        $canonicalMissing = is_array($crawl['canonicalAudit']['missing'] ?? null) ? $crawl['canonicalAudit']['missing'] : [];
        if ($canonicalMissing !== []) {
            $applySignal('missing_canonical');
        }

        $hreflangConflicts = is_array($crawl['hreflangAudit']['duplicateLangTags'] ?? null) ? $crawl['hreflangAudit']['duplicateLangTags'] : [];
        if ($hreflangConflicts !== []) {
            $applySignal('hreflang_conflicts');
        }

        $sitemapNon200 = is_array($sitemapAudit['non200'] ?? null) ? $sitemapAudit['non200'] : [];
        if ($sitemapNon200 !== []) {
            $applySignal('sitemap_non_200_urls');
        }

        $score += $this->profilePolicy->baseBonus($profileKey);
        $score = max(self::SCORE_MIN, min(self::SCORE_MAX, $score));
        $levelThresholds = $this->profilePolicy->levelThresholds($profileKey);
        $level = SiteIntelScoreLevel::fromThresholds(
            $score,
            $levelThresholds['high'],
            $levelThresholds['medium'],
        )->value;

        return [
            'value' => $score,
            'level' => $level,
            'profile' => $profileKey,
            'signals' => $signals,
        ];
    }
}
