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
}

