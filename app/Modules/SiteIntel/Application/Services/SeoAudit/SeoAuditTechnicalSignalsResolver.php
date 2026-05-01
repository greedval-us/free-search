<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditTechnicalSignalsResolver
{
    /**
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function resolveIndexability(array $headers, array $meta): array
    {
        $metaRobots = mb_strtolower((string) ($meta['robots'] ?? ''));
        $xRobotsTag = $this->headerFirst($headers, 'x-robots-tag');
        $xRobots = mb_strtolower($xRobotsTag);
        $isNoindex = str_contains($metaRobots, 'noindex') || str_contains($xRobots, 'noindex');

        return [
            'metaRobots' => (string) ($meta['robots'] ?? ''),
            'xRobotsTag' => $xRobotsTag,
            'indexable' => !$isNoindex,
            'reason' => $isNoindex ? 'noindex' : 'indexable',
        ];
    }

    /**
     * @param  array<string, mixed>  $fetchResult
     * @return array<string, mixed>
     */
    public function estimatePerformance(array $fetchResult): array
    {
        $body = (string) ($fetchResult['body'] ?? '');
        $resourceCount = preg_match_all('/<(script|link|img)\b/i', $body) ?: 0;
        $pageSizeKb = (int) round(strlen($body) / 1024);

        return [
            'ttfbMsApprox' => (int) ($fetchResult['responseTimeMs'] ?? 0),
            'pageSizeKb' => $pageSizeKb,
            'resourceCount' => $resourceCount,
            'renderBlocking' => $this->resolveRenderBlocking($body),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveMobileFriendly(string $html): array
    {
        $viewport = '';
        if (preg_match('/<meta[^>]+name\s*=\s*(["\'])viewport\1[^>]+content\s*=\s*(["\'])(.*?)\2[^>]*>/is', $html, $match) === 1) {
            $viewport = trim((string) ($match[3] ?? ''));
        }

        $viewportLower = mb_strtolower($viewport);
        $hasDeviceWidth = str_contains($viewportLower, 'width=device-width');
        $isResponsive = $viewport !== '' && $hasDeviceWidth;

        return [
            'hasViewportTag' => $viewport !== '',
            'viewportContent' => $viewport,
            'hasDeviceWidth' => $hasDeviceWidth,
            'isResponsive' => $isResponsive,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvePaginationSignals(string $html): array
    {
        $hasPrev = preg_match('/<link[^>]+rel\s*=\s*(["\'])prev\1/i', $html) === 1;
        $hasNext = preg_match('/<link[^>]+rel\s*=\s*(["\'])next\1/i', $html) === 1;

        return [
            'hasRelPrev' => $hasPrev,
            'hasRelNext' => $hasNext,
            'isPaginated' => $hasPrev || $hasNext,
        ];
    }

    public function detectSoft404(string $html, int $statusCode): array
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? ''));
        $markers = [
            'page not found',
            '404',
            'not found',
            'страница не найдена',
            'ничего не найдено',
        ];

        $matched = [];
        foreach ($markers as $marker) {
            if ($normalized !== '' && str_contains($normalized, $marker)) {
                $matched[] = $marker;
            }
        }

        $isSoft404 = $statusCode >= 200 && $statusCode < 300 && $matched !== [];

        return [
            'detected' => $isSoft404,
            'markers' => $matched,
        ];
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public function resolveSecurity(string $finalUrl, string $html, array $headers): array
    {
        $https = str_starts_with($finalUrl, 'https://');
        $mixedContent = $https && preg_match('/<(script|img|link)\b[^>]+(?:src|href)\s*=\s*(["\'])http:\/\//i', $html) === 1;

        return [
            'https' => $https,
            'mixedContent' => $mixedContent,
            'hasCsp' => $this->headerFirst($headers, 'content-security-policy') !== '',
            'hasHsts' => $this->headerFirst($headers, 'strict-transport-security') !== '',
        ];
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    private function headerFirst(array $headers, string $name): string
    {
        foreach ($headers as $headerName => $value) {
            if (mb_strtolower((string) $headerName) !== mb_strtolower($name)) {
                continue;
            }

            if (is_array($value)) {
                return isset($value[0]) ? (string) $value[0] : '';
            }

            return (string) $value;
        }

        return '';
    }

    /**
     * @return array<string, int>
     */
    private function resolveRenderBlocking(string $html): array
    {
        $blockingCss = preg_match_all('/<link\b[^>]*rel\s*=\s*(["\'])stylesheet\1[^>]*>/i', $html) ?: 0;

        $blockingScripts = 0;
        preg_match_all('/<script\b[^>]*src\s*=\s*(["\'])(.*?)\1[^>]*>/is', $html, $scriptTags);
        foreach (($scriptTags[0] ?? []) as $tag) {
            $lower = mb_strtolower((string) $tag);
            if (!str_contains($lower, ' defer') && !str_contains($lower, ' async')) {
                $blockingScripts++;
            }
        }

        return [
            'css' => $blockingCss,
            'scripts' => $blockingScripts,
            'total' => $blockingCss + $blockingScripts,
        ];
    }
}
