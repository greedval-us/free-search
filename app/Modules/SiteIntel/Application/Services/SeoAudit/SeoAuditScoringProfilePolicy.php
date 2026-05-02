<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditScoringProfilePolicy
{
    /**
     * @return array<string, float>
     */
    public function weights(string $profileKey): array
    {
        $default = [
            'meta' => 1.0,
            'performance' => 1.0,
            'render_blocking' => 1.0,
            'text_ratio' => 1.0,
            'page_size' => 1.0,
            'crawl_budget_hits' => 1.0,
            'crawl_budget_5xx' => 1.0,
        ];

        return match ($profileKey) {
            'media-platform' => [
                'meta' => 1.0,
                'performance' => 0.75,
                'render_blocking' => 0.45,
                'text_ratio' => 0.4,
                'page_size' => 0.5,
                'crawl_budget_hits' => 0.0,
                'crawl_budget_5xx' => 0.5,
            ],
            'storefront' => [
                'meta' => 1.0,
                'performance' => 0.9,
                'render_blocking' => 0.8,
                'text_ratio' => 0.8,
                'page_size' => 0.8,
                'crawl_budget_hits' => 0.6,
                'crawl_budget_5xx' => 0.9,
            ],
            'content-site' => [
                'meta' => 1.0,
                'performance' => 1.0,
                'render_blocking' => 0.9,
                'text_ratio' => 1.0,
                'page_size' => 1.0,
                'crawl_budget_hits' => 0.8,
                'crawl_budget_5xx' => 1.0,
            ],
            default => $default,
        };
    }

    public function baseBonus(string $profileKey): int
    {
        return match ($profileKey) {
            'media-platform' => 8,
            'storefront' => 3,
            default => 0,
        };
    }

    /**
     * @return array{high: int, medium: int}
     */
    public function levelThresholds(string $profileKey): array
    {
        return match ($profileKey) {
            'media-platform' => ['high' => 78, 'medium' => 50],
            'storefront' => ['high' => 80, 'medium' => 54],
            'content-site' => ['high' => 80, 'medium' => 55],
            default => ['high' => 80, 'medium' => 55],
        };
    }
}

