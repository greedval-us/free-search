<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditInternationalAnalyzer
{
    /**
     * @param array<string, mixed> $crawl
     * @return array<string, mixed>
     */
    public function analyze(array $crawl): array
    {
        $pages = is_array($crawl['pages'] ?? null) ? $crawl['pages'] : [];
        $clusters = [];
        $missingXDefault = [];
        $missingReciprocal = [];

        foreach ($pages as $page) {
            $url = (string) ($page['url'] ?? '');
            $tags = is_array($page['hreflang']['tags'] ?? null) ? $page['hreflang']['tags'] : [];
            if ($tags === []) {
                continue;
            }

            $langs = [];
            $hasXDefault = false;
            foreach ($tags as $tag) {
                $lang = mb_strtolower((string) ($tag['lang'] ?? ''));
                $href = (string) ($tag['href'] ?? '');
                if ($lang === '') {
                    continue;
                }
                $langs[] = $lang;
                if ($lang === 'x-default') {
                    $hasXDefault = true;
                }

                $target = $this->findPageByUrl($pages, $href);
                if ($target !== null && !$this->hasReciprocalReference($target, $url)) {
                    $missingReciprocal[] = [
                        'source' => $url,
                        'target' => $href,
                        'lang' => $lang,
                    ];
                }
            }

            sort($langs);
            $clusterKey = implode('|', array_values(array_unique($langs)));
            $clusters[$clusterKey] = ($clusters[$clusterKey] ?? 0) + 1;

            if (!$hasXDefault) {
                $missingXDefault[] = $url;
            }
        }

        $clusterRows = [];
        foreach ($clusters as $langs => $count) {
            $clusterRows[] = [
                'langs' => $langs,
                'count' => $count,
            ];
        }

        return [
            'clusters' => $clusterRows,
            'missingXDefault' => $missingXDefault,
            'missingReciprocal' => $missingReciprocal,
            'pagesWithHreflang' => (int) ($crawl['hreflangAudit']['pagesWithHreflang'] ?? 0),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $pages
     * @return array<string, mixed>|null
     */
    private function findPageByUrl(array $pages, string $url): ?array
    {
        $needle = $this->normalizeUrl($url);
        foreach ($pages as $page) {
            if ($this->normalizeUrl((string) ($page['url'] ?? '')) === $needle) {
                return $page;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $page
     */
    private function hasReciprocalReference(array $page, string $url): bool
    {
        $tags = is_array($page['hreflang']['tags'] ?? null) ? $page['hreflang']['tags'] : [];
        $needle = $this->normalizeUrl($url);
        foreach ($tags as $tag) {
            if ($this->normalizeUrl((string) ($tag['href'] ?? '')) === $needle) {
                return true;
            }
        }

        return false;
    }

    private function normalizeUrl(string $url): string
    {
        return rtrim(mb_strtolower(trim($url)), '/');
    }
}

