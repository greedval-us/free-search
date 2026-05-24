<?php

namespace App\Modules\Telegram\Support;

final class TelegramConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config, string $timezone): self
    {
        $analytics = self::arrayAt($config, ['analytics']);
        $fetch = self::arrayAt($analytics, ['fetch']);
        $audience = self::arrayAt($analytics, ['audience']);
        $fraud = self::arrayAt($analytics, ['fraud']);
        $scoreProfiles = self::arrayAt($analytics, ['score_profiles']);

        $riskMedium = max(0, self::intValue($fraud, ['risk_medium_threshold'], 30));

        return new self(
            timezone: trim($timezone) !== '' ? $timezone : 'UTC',
            analyticsSummaryCacheTtlSeconds: max(1, self::intValue($analytics, ['summary_cache_ttl_seconds'], 60)),
            analyticsFetchMaxPages: max(1, self::intValue($fetch, ['max_pages'], 20)),
            analyticsFetchPageLimit: max(1, self::intValue($fetch, ['page_limit'], 100)),
            analyticsGroupByHourThresholdHours: max(1, self::intValue($analytics, ['group_by_hour_threshold_hours'], 36)),
            analyticsPeriodMaxDays: max(1, self::intValue($analytics, ['period_max_days'], 7)),
            analyticsTopPostsLimit: max(1, self::intValue($analytics, ['top_posts_limit'], 5)),
            analyticsTopDistributionLimit: max(1, self::intValue($analytics, ['top_distribution_limit'], 5)),
            analyticsAudienceTopAuthorsShareLimit: max(1, self::intValue($audience, ['top_authors_share_limit'], 5)),
            analyticsAudienceMostActiveHoursLimit: max(1, self::intValue($audience, ['most_active_hours_limit'], 3)),
            analyticsAudienceHourMin: self::intValue($audience, ['hour_min'], 0),
            analyticsAudienceHourMax: self::intValue($audience, ['hour_max'], 23),
            analyticsFraudPostRules: self::arrayAt($fraud, ['post_rules']),
            analyticsFraudTriggerRules: self::arrayAt($fraud, ['triggers']),
            analyticsFraudSuspiciousPostExcerptLength: max(1, self::intValue($fraud, ['suspicious_post_excerpt_length'], 160)),
            analyticsFraudSuspiciousPostsLimit: max(1, self::intValue($fraud, ['suspicious_posts_limit'], 5)),
            analyticsFraudRiskMaxScore: max(1, self::intValue($fraud, ['risk_max_score'], 100)),
            analyticsFraudRiskMediumThreshold: $riskMedium,
            analyticsFraudRiskHighThreshold: max($riskMedium, self::intValue($fraud, ['risk_high_threshold'], 60)),
            analyticsScoreProfiles: self::normalizeScoreProfiles($scoreProfiles),
        );
    }

    /**
     * @param array<string, mixed> $analyticsFraudPostRules
     * @param array<string, mixed> $analyticsFraudTriggerRules
     * @param array<string, array{views: float, forwards: float, replies: float, reactions: float, gifts: float}> $analyticsScoreProfiles
     */
    public function __construct(
        private readonly string $timezone,
        private readonly int $analyticsSummaryCacheTtlSeconds,
        private readonly int $analyticsFetchMaxPages,
        private readonly int $analyticsFetchPageLimit,
        private readonly int $analyticsGroupByHourThresholdHours,
        private readonly int $analyticsPeriodMaxDays,
        private readonly int $analyticsTopPostsLimit,
        private readonly int $analyticsTopDistributionLimit,
        private readonly int $analyticsAudienceTopAuthorsShareLimit,
        private readonly int $analyticsAudienceMostActiveHoursLimit,
        private readonly int $analyticsAudienceHourMin,
        private readonly int $analyticsAudienceHourMax,
        private readonly array $analyticsFraudPostRules,
        private readonly array $analyticsFraudTriggerRules,
        private readonly int $analyticsFraudSuspiciousPostExcerptLength,
        private readonly int $analyticsFraudSuspiciousPostsLimit,
        private readonly int $analyticsFraudRiskMaxScore,
        private readonly int $analyticsFraudRiskMediumThreshold,
        private readonly int $analyticsFraudRiskHighThreshold,
        private readonly array $analyticsScoreProfiles,
    ) {
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function analyticsSummaryCacheTtlSeconds(): int
    {
        return $this->analyticsSummaryCacheTtlSeconds;
    }

    public function analyticsFetchMaxPages(): int
    {
        return $this->analyticsFetchMaxPages;
    }

    public function analyticsFetchPageLimit(): int
    {
        return $this->analyticsFetchPageLimit;
    }

    public function analyticsGroupByHourThresholdHours(): int
    {
        return $this->analyticsGroupByHourThresholdHours;
    }

    public function analyticsPeriodMaxDays(): int
    {
        return $this->analyticsPeriodMaxDays;
    }

    public function analyticsTopPostsLimit(): int
    {
        return $this->analyticsTopPostsLimit;
    }

    public function analyticsTopDistributionLimit(): int
    {
        return $this->analyticsTopDistributionLimit;
    }

    public function analyticsAudienceTopAuthorsShareLimit(): int
    {
        return $this->analyticsAudienceTopAuthorsShareLimit;
    }

    public function analyticsAudienceMostActiveHoursLimit(): int
    {
        return $this->analyticsAudienceMostActiveHoursLimit;
    }

    public function analyticsAudienceHourMin(): int
    {
        return $this->analyticsAudienceHourMin;
    }

    public function analyticsAudienceHourMax(): int
    {
        return $this->analyticsAudienceHourMax;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyticsFraudPostRules(): array
    {
        return $this->analyticsFraudPostRules;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyticsFraudTriggerRules(): array
    {
        return $this->analyticsFraudTriggerRules;
    }

    public function analyticsFraudSuspiciousPostExcerptLength(): int
    {
        return $this->analyticsFraudSuspiciousPostExcerptLength;
    }

    public function analyticsFraudSuspiciousPostsLimit(): int
    {
        return $this->analyticsFraudSuspiciousPostsLimit;
    }

    public function analyticsFraudRiskMaxScore(): int
    {
        return $this->analyticsFraudRiskMaxScore;
    }

    public function analyticsFraudRiskMediumThreshold(): int
    {
        return $this->analyticsFraudRiskMediumThreshold;
    }

    public function analyticsFraudRiskHighThreshold(): int
    {
        return $this->analyticsFraudRiskHighThreshold;
    }

    /**
     * @return array<string, array{views: float, forwards: float, replies: float, reactions: float, gifts: float}>
     */
    public function analyticsScoreProfiles(): array
    {
        return $this->analyticsScoreProfiles;
    }

    /**
     * @param array<string, mixed> $scoreProfiles
     * @return array<string, array{views: float, forwards: float, replies: float, reactions: float, gifts: float}>
     */
    private static function normalizeScoreProfiles(array $scoreProfiles): array
    {
        $defaults = self::defaultScoreProfiles();
        $normalized = [];

        foreach ($defaults as $profile => $weights) {
            $candidate = $scoreProfiles[$profile] ?? null;
            if (!is_array($candidate)) {
                $normalized[$profile] = $weights;
                continue;
            }

            $normalized[$profile] = [
                'views' => (float) ($candidate['views'] ?? $weights['views']),
                'forwards' => (float) ($candidate['forwards'] ?? $weights['forwards']),
                'replies' => (float) ($candidate['replies'] ?? $weights['replies']),
                'reactions' => (float) ($candidate['reactions'] ?? $weights['reactions']),
                'gifts' => (float) ($candidate['gifts'] ?? $weights['gifts']),
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, array{views: float, forwards: float, replies: float, reactions: float, gifts: float}>
     */
    private static function defaultScoreProfiles(): array
    {
        return [
            'balanced' => [
                'views' => 1.0,
                'forwards' => 5.0,
                'replies' => 8.0,
                'reactions' => 2.0,
                'gifts' => 10.0,
            ],
            'reach' => [
                'views' => 3.0,
                'forwards' => 4.0,
                'replies' => 2.0,
                'reactions' => 1.0,
                'gifts' => 2.0,
            ],
            'discussion' => [
                'views' => 1.0,
                'forwards' => 3.0,
                'replies' => 12.0,
                'reactions' => 2.0,
                'gifts' => 3.0,
            ],
            'virality' => [
                'views' => 1.0,
                'forwards' => 10.0,
                'replies' => 6.0,
                'reactions' => 3.0,
                'gifts' => 5.0,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     * @return array<string, mixed>
     */
    private static function arrayAt(array $config, array $path): array
    {
        $value = self::valueByPath($config, $path);

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private static function intValue(array $config, array $path, int $default): int
    {
        $value = self::valueByPath($config, $path);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private static function valueByPath(array $config, array $path): mixed
    {
        $cursor = $config;

        foreach ($path as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }
}
