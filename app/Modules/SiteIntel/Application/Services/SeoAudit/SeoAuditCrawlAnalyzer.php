<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditCrawlAnalyzer
{
    public function __construct(
        private readonly SeoAuditHttpFetcher $httpFetcher,
        private readonly SeoAuditContentExtractor $contentExtractor,
        private readonly SeoAuditTechnicalSignalsResolver $technicalSignalsResolver,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $startUrl, int $limit): array
    {
        $host = (string) parse_url($startUrl, PHP_URL_HOST);
        if ($host === '') {
            return $this->emptyResult($limit);
        }

        $visited = [];
        $queue = [$startUrl];
        $pages = [];

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
            'duplicates' => $this->buildDuplicates($pages),
            'canonicalAudit' => $this->buildCanonicalAudit($pages, $host),
            'hreflangAudit' => $this->buildHreflangAudit($pages),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildDuplicates(array $pages): array
    {
        return [
            'titles' => $this->collectDuplicates($pages, 'title'),
            'descriptions' => $this->collectDuplicates($pages, 'description'),
            'h1' => $this->collectDuplicates($pages, 'h1Count'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     * @return array<int, array<string, mixed>>
     */
    private function collectDuplicates(array $pages, string $field): array
    {
        $bucket = [];
        foreach ($pages as $page) {
            $value = $page[$field] ?? null;
            $key = trim((string) $value);
            if ($key === '' || $key === '0' || $key === '1') {
                continue;
            }

            $bucket[$key][] = (string) ($page['url'] ?? '');
        }

        $duplicates = [];
        foreach ($bucket as $key => $urls) {
            if (count($urls) < 2) {
                continue;
            }

            $duplicates[] = [
                'value' => $key,
                'count' => count($urls),
                'urls' => $urls,
            ];
        }

        return $duplicates;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     * @return array<string, mixed>
     */
    private function buildCanonicalAudit(array $pages, string $host): array
    {
        $missing = [];
        $crossDomain = [];
        $invalid = [];
        $selfReferencing = 0;

        foreach ($pages as $page) {
            $url = (string) ($page['url'] ?? '');
            $canonical = trim((string) ($page['canonical'] ?? ''));
            if ($canonical === '') {
                $missing[] = $url;
                continue;
            }

            $canonicalHost = (string) parse_url($canonical, PHP_URL_HOST);
            if ($canonicalHost !== '' && mb_strtolower($canonicalHost) !== mb_strtolower($host)) {
                $crossDomain[] = ['url' => $url, 'canonical' => $canonical];
            }

            if (!$this->isValidCanonical($canonical)) {
                $invalid[] = ['url' => $url, 'canonical' => $canonical];
            }

            if ($this->normalizeUrl($url) === $this->normalizeUrl($canonical)) {
                $selfReferencing++;
            }
        }

        return [
            'missing' => $missing,
            'crossDomain' => $crossDomain,
            'invalid' => $invalid,
            'selfReferencing' => $selfReferencing,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     * @return array<string, mixed>
     */
    private function buildHreflangAudit(array $pages): array
    {
        $pagesWithHreflang = 0;
        $pagesWithoutSelf = [];
        $duplicateLangTags = [];

        foreach ($pages as $page) {
            $url = (string) ($page['url'] ?? '');
            $hreflang = is_array($page['hreflang'] ?? null) ? $page['hreflang'] : ['tags' => [], 'count' => 0];
            $tags = is_array($hreflang['tags'] ?? null) ? $hreflang['tags'] : [];

            if ($tags !== []) {
                $pagesWithHreflang++;
            }

            $langs = [];
            $hasSelf = false;
            foreach ($tags as $tag) {
                $lang = mb_strtolower((string) ($tag['lang'] ?? ''));
                $href = (string) ($tag['href'] ?? '');
                if ($lang !== '') {
                    $langs[$lang] = ($langs[$lang] ?? 0) + 1;
                }
                if ($this->normalizeUrl($href) === $this->normalizeUrl($url)) {
                    $hasSelf = true;
                }
            }

            if ($tags !== [] && !$hasSelf) {
                $pagesWithoutSelf[] = $url;
            }

            foreach ($langs as $lang => $count) {
                if ($count > 1) {
                    $duplicateLangTags[] = [
                        'url' => $url,
                        'lang' => $lang,
                        'count' => $count,
                    ];
                }
            }
        }

        return [
            'pagesWithHreflang' => $pagesWithHreflang,
            'pagesWithoutSelfReference' => $pagesWithoutSelf,
            'duplicateLangTags' => $duplicateLangTags,
        ];
    }

    private function isValidCanonical(string $canonical): bool
    {
        return str_starts_with($canonical, 'http://') || str_starts_with($canonical, 'https://') || str_starts_with($canonical, '/');
    }

    private function normalizeUrl(string $url): string
    {
        return rtrim(mb_strtolower(trim($url)), '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(int $limit): array
    {
        return [
            'limit' => $limit,
            'scanned' => 0,
            'pages' => [],
            'duplicates' => [
                'titles' => [],
                'descriptions' => [],
                'h1' => [],
            ],
            'canonicalAudit' => [
                'missing' => [],
                'crossDomain' => [],
                'invalid' => [],
                'selfReferencing' => 0,
            ],
            'hreflangAudit' => [
                'pagesWithHreflang' => 0,
                'pagesWithoutSelfReference' => [],
                'duplicateLangTags' => [],
            ],
        ];
    }
}
