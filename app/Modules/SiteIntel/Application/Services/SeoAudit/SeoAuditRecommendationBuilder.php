<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

use App\Modules\SiteIntel\Enums\SiteIntelRecommendationPriority;

final class SeoAuditRecommendationBuilder
{
    public function __construct(
        private readonly SeoAuditTechnicalThresholds $thresholds,
    ) {
    }

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
     * @return array<int, array{priority: string, key: string}>
     */
    public function build(
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
        $items = [];

        if (($indexability['indexable'] ?? false) !== true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Critical->value, 'key' => 'enable_indexing'];
        }

        if (($security['https'] ?? false) !== true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Critical->value, 'key' => 'enable_https'];
        }

        if ($this->thresholds->isTitleOutOfRange((int) ($meta['titleLength'] ?? 0))) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'improve_title_length'];
        }

        if ($this->thresholds->isDescriptionOutOfRange((int) ($meta['descriptionLength'] ?? 0))) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'improve_description_length'];
        }

        if (($headings['h1'] ?? 0) !== 1) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'fix_h1_count'];
        }

        if (($robots['available'] ?? false) !== true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'add_robots_txt'];
        }

        if (($sitemap['available'] ?? false) !== true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'add_sitemap'];
        }

        if ($this->thresholds->isSlowTtfb((int) ($performance['ttfbMsApprox'] ?? 0))) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'improve_ttfb'];
        }

        if ($this->thresholds->isHeavyPage((int) ($performance['pageSizeKb'] ?? 0))) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Low->value, 'key' => 'reduce_page_size'];
        }

        if (($security['mixedContent'] ?? false) === true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'remove_mixed_content'];
        }
        if (($mobileFriendly['isResponsive'] ?? false) !== true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'add_viewport_meta'];
        }
        if (($soft404['detected'] ?? false) === true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Critical->value, 'key' => 'fix_soft_404'];
        }
        if ($this->thresholds->hasHighRenderBlocking((int) ($performance['renderBlocking']['total'] ?? 0))) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'reduce_render_blocking'];
        }
        if (($pagination['isPaginated'] ?? false) === true && !(($pagination['hasRelPrev'] ?? false) && ($pagination['hasRelNext'] ?? false))) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Low->value, 'key' => 'fix_pagination_rel_links'];
        }
        if ((int) ($quality['anchors']['empty'] ?? 0) > 0) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'fix_empty_anchor_text'];
        }
        if ((int) ($quality['accessibility']['imagesWithoutAlt'] ?? 0) > 0) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'add_image_alt_attributes'];
        }
        if (($quality['content']['thinContent'] ?? false) === true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'expand_thin_content'];
        }
        if (($quality['content']['lowTextRatio'] ?? false) === true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Low->value, 'key' => 'improve_text_html_ratio'];
        }
        if (($quality['linkGraph']['orphanRisk'] ?? false) === true) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'improve_internal_linking'];
        }
        if ((int) ($quality['htmlValidation']['issueCount'] ?? 0) > 0) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Low->value, 'key' => 'fix_html_structure'];
        }
        if (is_array($international['missingReciprocal'] ?? null) && $international['missingReciprocal'] !== []) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'fix_hreflang_reciprocal_links'];
        }
        if (is_array($international['missingXDefault'] ?? null) && $international['missingXDefault'] !== []) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Low->value, 'key' => 'add_hreflang_x_default'];
        }
        if ((int) ($crawlBudget['botHits'] ?? 0) === 0) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Low->value, 'key' => 'verify_bot_access_in_logs'];
        }
        if ((int) ($crawlBudget['statusBuckets']['5xx'] ?? 0) > 0) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'reduce_bot_5xx_errors'];
        }

        if (is_array($crawl['duplicates']['titles'] ?? null) && $crawl['duplicates']['titles'] !== []) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'deduplicate_titles'];
        }

        if (is_array($crawl['canonicalAudit']['missing'] ?? null) && $crawl['canonicalAudit']['missing'] !== []) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'set_canonical_on_all_pages'];
        }

        if (is_array($crawl['hreflangAudit']['pagesWithoutSelfReference'] ?? null) && $crawl['hreflangAudit']['pagesWithoutSelfReference'] !== []) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Low->value, 'key' => 'fix_hreflang_self_reference'];
        }

        if (is_array($sitemapAudit['non200'] ?? null) && $sitemapAudit['non200'] !== []) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Medium->value, 'key' => 'fix_sitemap_non_200_urls'];
        }

        if ($items === []) {
            $items[] = ['priority' => SiteIntelRecommendationPriority::Low->value, 'key' => 'maintain_current_setup'];
        }

        return array_values(array_unique($items, SORT_REGULAR));
    }
}
