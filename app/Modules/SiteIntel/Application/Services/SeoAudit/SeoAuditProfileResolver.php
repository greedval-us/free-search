<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditProfileResolver
{
    /**
     * @param  array<string, mixed>  $performance
     * @param  array<string, mixed>  $quality
     * @param  array<string, mixed>  $crawl
     * @return array{key: string, label: string}
     */
    public function resolve(array $performance, array $quality, array $crawl, ?string $forcedProfile = null): array
    {
        if ($forcedProfile !== null) {
            return $this->profileByKey($forcedProfile);
        }

        $resourceCount = (int) ($performance['resourceCount'] ?? 0);
        $renderBlocking = (int) ($performance['renderBlocking']['total'] ?? 0);
        $textRatio = (float) ($quality['content']['textToHtmlRatio'] ?? 0.0);
        $wordCount = (int) ($quality['content']['wordCount'] ?? 0);
        $scanned = (int) ($crawl['scanned'] ?? 0);

        if ($resourceCount >= 120 && $renderBlocking >= 6 && $textRatio < 12.0) {
            return $this->profileByKey('media-platform');
        }

        if ($wordCount >= 900 && $textRatio >= 14.0) {
            return $this->profileByKey('content-site');
        }

        if ($scanned >= 6 && $resourceCount >= 60 && $textRatio >= 8.0 && $textRatio <= 22.0) {
            return $this->profileByKey('storefront');
        }

        return $this->profileByKey('generic');
    }

    /**
     * @return array{key: string, label: string}
     */
    private function profileByKey(string $key): array
    {
        return match ($key) {
            'media-platform' => ['key' => 'media-platform', 'label' => 'Media Platform'],
            'content-site' => ['key' => 'content-site', 'label' => 'Content Site'],
            'storefront' => ['key' => 'storefront', 'label' => 'Storefront'],
            default => ['key' => 'generic', 'label' => 'Generic'],
        };
    }
}
