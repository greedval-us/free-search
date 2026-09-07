<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditScoringProfilePolicy
{
    private const DEFAULT_WEIGHTS = [
        'meta' => 1.0,
        'performance' => 1.0,
        'render_blocking' => 1.0,
        'text_ratio' => 1.0,
        'page_size' => 1.0,
        'crawl_budget_hits' => 1.0,
        'crawl_budget_5xx' => 1.0,
    ];

    private const WEIGHT_OVERRIDES = [
        'media-platform' => [
            'performance' => 0.75,
            'render_blocking' => 0.45,
            'text_ratio' => 0.4,
            'page_size' => 0.5,
            'crawl_budget_hits' => 0.0,
            'crawl_budget_5xx' => 0.5,
        ],
        'storefront' => [
            'performance' => 0.9,
            'render_blocking' => 0.8,
            'text_ratio' => 0.8,
            'page_size' => 0.8,
            'crawl_budget_hits' => 0.6,
            'crawl_budget_5xx' => 0.9,
        ],
        'content-site' => [
            'render_blocking' => 0.9,
            'crawl_budget_hits' => 0.8,
        ],
    ];

    private const BASE_BONUSES = [
        'media-platform' => 8,
        'storefront' => 3,
    ];

    private const DEFAULT_LEVEL_THRESHOLDS = ['high' => 80, 'medium' => 55];

    private const LEVEL_THRESHOLD_OVERRIDES = [
        'media-platform' => ['high' => 78, 'medium' => 50],
        'storefront' => ['high' => 80, 'medium' => 54],
    ];

    /**
     * @return array<string, float>
     */
    public function weights(string $profileKey): array
    {
        return array_replace(
            self::DEFAULT_WEIGHTS,
            self::WEIGHT_OVERRIDES[$profileKey] ?? [],
        );
    }

    public function baseBonus(string $profileKey): int
    {
        return self::BASE_BONUSES[$profileKey] ?? 0;
    }

    /**
     * @return array{high: int, medium: int}
     */
    public function levelThresholds(string $profileKey): array
    {
        return self::LEVEL_THRESHOLD_OVERRIDES[$profileKey] ?? self::DEFAULT_LEVEL_THRESHOLDS;
    }
}
