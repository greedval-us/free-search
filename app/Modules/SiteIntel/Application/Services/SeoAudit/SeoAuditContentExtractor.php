<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditContentExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extractMeta(string $html): array
    {
        $title = $this->matchOne('/<title[^>]*>(.*?)<\/title>/is', $html);
        $description = $this->extractMetaByName($html, 'description');
        $canonical = $this->extractCanonical($html);
        $robots = $this->extractMetaByName($html, 'robots');

        return [
            'title' => $title,
            'titleLength' => mb_strlen($title),
            'description' => $description,
            'descriptionLength' => mb_strlen($description),
            'canonical' => $canonical,
            'robots' => $robots,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function extractHeadings(string $html): array
    {
        return [
            'h1' => $this->countMatches('/<h1\b[^>]*>/i', $html),
            'h2' => $this->countMatches('/<h2\b[^>]*>/i', $html),
            'h3' => $this->countMatches('/<h3\b[^>]*>/i', $html),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function extractLinks(string $html, string $host): array
    {
        if ($html === '') {
            return ['internal' => 0, 'external' => 0, 'nofollow' => 0];
        }

        preg_match_all('/<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>/is', $html, $matches, PREG_SET_ORDER);

        $internal = 0;
        $external = 0;
        $nofollow = 0;

        foreach ($matches as $match) {
            $tag = $match[0] ?? '';
            $href = trim((string) ($match[2] ?? ''));

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            if (stripos($tag, 'rel=') !== false && preg_match('/rel\s*=\s*(["\'])(.*?)\1/is', $tag, $rel) === 1) {
                if (str_contains(mb_strtolower((string) ($rel[2] ?? '')), 'nofollow')) {
                    $nofollow++;
                }
            }

            if (str_starts_with($href, '/')) {
                $internal++;
                continue;
            }

            $hrefHost = (string) parse_url($href, PHP_URL_HOST);
            if ($hrefHost === '' || $host === '') {
                $internal++;
                continue;
            }

            if (mb_strtolower($hrefHost) === mb_strtolower($host)) {
                $internal++;
            } else {
                $external++;
            }
        }

        return [
            'internal' => $internal,
            'external' => $external,
            'nofollow' => $nofollow,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function extractHreflang(string $html): array
    {
        preg_match_all('/<link\b[^>]*rel\s*=\s*(["\'])alternate\1[^>]*>/is', $html, $matches);

        $tags = [];
        foreach (($matches[0] ?? []) as $tag) {
            if (preg_match('/hreflang\s*=\s*(["\'])(.*?)\1/is', $tag, $langMatch) !== 1) {
                continue;
            }
            if (preg_match('/href\s*=\s*(["\'])(.*?)\1/is', $tag, $hrefMatch) !== 1) {
                continue;
            }

            $tags[] = [
                'lang' => trim((string) ($langMatch[2] ?? '')),
                'href' => trim((string) ($hrefMatch[2] ?? '')),
            ];
        }

        return [
            'count' => count($tags),
            'tags' => $tags,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function extractCrawlableLinks(string $html, string $baseUrl, string $host): array
    {
        if ($html === '') {
            return [];
        }

        preg_match_all('/<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>/is', $html, $matches, PREG_SET_ORDER);
        $urls = [];

        foreach ($matches as $match) {
            $href = trim((string) ($match[2] ?? ''));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            $resolved = $this->resolveUrl($baseUrl, $href);
            if ($resolved === null) {
                continue;
            }

            $linkHost = (string) parse_url($resolved, PHP_URL_HOST);
            if ($linkHost === '' || mb_strtolower($linkHost) !== mb_strtolower($host)) {
                continue;
            }

            $urls[] = $resolved;
        }

        return array_values(array_unique($urls));
    }

    private function matchOne(string $pattern, string $html): string
    {
        if (preg_match($pattern, $html, $match) !== 1) {
            return '';
        }

        return trim(strip_tags(html_entity_decode((string) ($match[1] ?? ''), ENT_QUOTES | ENT_HTML5)));
    }

    private function extractMetaByName(string $html, string $name): string
    {
        $pattern = sprintf('/<meta[^>]+name\s*=\s*(["\'])%s\1[^>]+content\s*=\s*(["\'])(.*?)\2[^>]*>/is', preg_quote($name, '/'));
        if (preg_match($pattern, $html, $match) === 1) {
            return trim((string) ($match[3] ?? ''));
        }

        $patternReverse = sprintf('/<meta[^>]+content\s*=\s*(["\'])(.*?)\1[^>]+name\s*=\s*(["\'])%s\3[^>]*>/is', preg_quote($name, '/'));
        if (preg_match($patternReverse, $html, $match) === 1) {
            return trim((string) ($match[2] ?? ''));
        }

        return '';
    }

    private function extractCanonical(string $html): string
    {
        if (preg_match('/<link[^>]+rel\s*=\s*(["\'])canonical\1[^>]+href\s*=\s*(["\'])(.*?)\2[^>]*>/is', $html, $match) === 1) {
            return trim((string) ($match[3] ?? ''));
        }

        if (preg_match('/<link[^>]+href\s*=\s*(["\'])(.*?)\1[^>]+rel\s*=\s*(["\'])canonical\3[^>]*>/is', $html, $match) === 1) {
            return trim((string) ($match[2] ?? ''));
        }

        return '';
    }

    private function countMatches(string $pattern, string $subject): int
    {
        return preg_match_all($pattern, $subject) ?: 0;
    }

    private function resolveUrl(string $baseUrl, string $href): ?string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        $parts = parse_url($baseUrl);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = (string) $parts['scheme'];
        $host = (string) $parts['host'];

        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }

        if (str_starts_with($href, '/')) {
            return sprintf('%s://%s%s', $scheme, $host, $href);
        }

        $path = (string) ($parts['path'] ?? '/');
        $basePath = str_ends_with($path, '/') ? $path : dirname($path) . '/';
        if ($basePath === './') {
            $basePath = '/';
        }

        return sprintf('%s://%s%s%s', $scheme, $host, $basePath, $href);
    }
}
