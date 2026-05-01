<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditRecommendationBuilder
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
            $items[] = ['priority' => 'critical', 'key' => 'enable_indexing'];
        }

        if (($security['https'] ?? false) !== true) {
            $items[] = ['priority' => 'critical', 'key' => 'enable_https'];
        }

        if (($meta['titleLength'] ?? 0) < 15 || ($meta['titleLength'] ?? 0) > 65) {
            $items[] = ['priority' => 'medium', 'key' => 'improve_title_length'];
        }

        if (($meta['descriptionLength'] ?? 0) < 50 || ($meta['descriptionLength'] ?? 0) > 170) {
            $items[] = ['priority' => 'medium', 'key' => 'improve_description_length'];
        }

        if (($headings['h1'] ?? 0) !== 1) {
            $items[] = ['priority' => 'medium', 'key' => 'fix_h1_count'];
        }

        if (($robots['available'] ?? false) !== true) {
            $items[] = ['priority' => 'medium', 'key' => 'add_robots_txt'];
        }

        if (($sitemap['available'] ?? false) !== true) {
            $items[] = ['priority' => 'medium', 'key' => 'add_sitemap'];
        }

        if (($performance['ttfbMsApprox'] ?? 0) > 1200) {
            $items[] = ['priority' => 'medium', 'key' => 'improve_ttfb'];
        }

        if (($performance['pageSizeKb'] ?? 0) > 1500) {
            $items[] = ['priority' => 'low', 'key' => 'reduce_page_size'];
        }

        if (($security['mixedContent'] ?? false) === true) {
            $items[] = ['priority' => 'medium', 'key' => 'remove_mixed_content'];
        }
        if (($mobileFriendly['isResponsive'] ?? false) !== true) {
            $items[] = ['priority' => 'medium', 'key' => 'add_viewport_meta'];
        }
        if (($soft404['detected'] ?? false) === true) {
            $items[] = ['priority' => 'critical', 'key' => 'fix_soft_404'];
        }
        if ((int) ($performance['renderBlocking']['total'] ?? 0) > 6) {
            $items[] = ['priority' => 'medium', 'key' => 'reduce_render_blocking'];
        }
        if (($pagination['isPaginated'] ?? false) === true && !(($pagination['hasRelPrev'] ?? false) && ($pagination['hasRelNext'] ?? false))) {
            $items[] = ['priority' => 'low', 'key' => 'fix_pagination_rel_links'];
        }
        if ((int) ($quality['anchors']['empty'] ?? 0) > 0) {
            $items[] = ['priority' => 'medium', 'key' => 'fix_empty_anchor_text'];
        }
        if ((int) ($quality['accessibility']['imagesWithoutAlt'] ?? 0) > 0) {
            $items[] = ['priority' => 'medium', 'key' => 'add_image_alt_attributes'];
        }
        if (($quality['content']['thinContent'] ?? false) === true) {
            $items[] = ['priority' => 'medium', 'key' => 'expand_thin_content'];
        }
        if (($quality['content']['lowTextRatio'] ?? false) === true) {
            $items[] = ['priority' => 'low', 'key' => 'improve_text_html_ratio'];
        }
        if (($quality['linkGraph']['orphanRisk'] ?? false) === true) {
            $items[] = ['priority' => 'medium', 'key' => 'improve_internal_linking'];
        }
        if ((int) ($quality['htmlValidation']['issueCount'] ?? 0) > 0) {
            $items[] = ['priority' => 'low', 'key' => 'fix_html_structure'];
        }
        if (is_array($international['missingReciprocal'] ?? null) && $international['missingReciprocal'] !== []) {
            $items[] = ['priority' => 'medium', 'key' => 'fix_hreflang_reciprocal_links'];
        }
        if (is_array($international['missingXDefault'] ?? null) && $international['missingXDefault'] !== []) {
            $items[] = ['priority' => 'low', 'key' => 'add_hreflang_x_default'];
        }
        if ((int) ($crawlBudget['botHits'] ?? 0) === 0) {
            $items[] = ['priority' => 'low', 'key' => 'verify_bot_access_in_logs'];
        }
        if ((int) ($crawlBudget['statusBuckets']['5xx'] ?? 0) > 0) {
            $items[] = ['priority' => 'medium', 'key' => 'reduce_bot_5xx_errors'];
        }

        if (is_array($crawl['duplicates']['titles'] ?? null) && $crawl['duplicates']['titles'] !== []) {
            $items[] = ['priority' => 'medium', 'key' => 'deduplicate_titles'];
        }

        if (is_array($crawl['canonicalAudit']['missing'] ?? null) && $crawl['canonicalAudit']['missing'] !== []) {
            $items[] = ['priority' => 'medium', 'key' => 'set_canonical_on_all_pages'];
        }

        if (is_array($crawl['hreflangAudit']['pagesWithoutSelfReference'] ?? null) && $crawl['hreflangAudit']['pagesWithoutSelfReference'] !== []) {
            $items[] = ['priority' => 'low', 'key' => 'fix_hreflang_self_reference'];
        }

        if (is_array($sitemapAudit['non200'] ?? null) && $sitemapAudit['non200'] !== []) {
            $items[] = ['priority' => 'medium', 'key' => 'fix_sitemap_non_200_urls'];
        }

        if ($items === []) {
            $items[] = ['priority' => 'low', 'key' => 'maintain_current_setup'];
        }

        return array_values(array_unique($items, SORT_REGULAR));
    }
}
