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
