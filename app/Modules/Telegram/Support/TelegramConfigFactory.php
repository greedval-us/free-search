<?php

namespace App\Modules\Telegram\Support;

final class TelegramConfigFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public function make(array $config, string $timezone): TelegramConfig
    {
        $analytics = $this->arrayAt($config, ['analytics']);
        $fetch = $this->arrayAt($analytics, ['fetch']);
        $audience = $this->arrayAt($analytics, ['audience']);
        $fraud = $this->arrayAt($analytics, ['fraud']);
        $scoreProfiles = $this->arrayAt($analytics, ['score_profiles']);
        $search = $this->arrayAt($config, ['search']);
        $parser = $this->arrayAt($config, ['parser']);

        $riskMedium = max(0, $this->intValue($fraud, ['risk_medium_threshold'], 30));
        $periodMinDays = max(1, $this->intValue($analytics, ['period_min_days'], 1));

        return new TelegramConfig(
            timezone: trim($timezone) !== '' ? $timezone : 'UTC',
            analyticsSummaryCacheTtlSeconds: max(1, $this->intValue($analytics, ['summary_cache_ttl_seconds'], 60)),
            analyticsFetchMaxPages: max(1, $this->intValue($fetch, ['max_pages'], 20)),
            analyticsFetchPageLimit: max(1, $this->intValue($fetch, ['page_limit'], 100)),
            analyticsGroupByHourThresholdHours: max(1, $this->intValue($analytics, ['group_by_hour_threshold_hours'], 36)),
            analyticsPeriodMinDays: $periodMinDays,
            analyticsPeriodMaxDays: max($periodMinDays, $this->intValue($analytics, ['period_max_days'], 7)),
            analyticsCustomRangeMaxDays: max(1, $this->intValue($analytics, ['custom_range_max_days'], 7)),
            analyticsTopPostsLimit: max(1, $this->intValue($analytics, ['top_posts_limit'], 5)),
            analyticsTopDistributionLimit: max(1, $this->intValue($analytics, ['top_distribution_limit'], 5)),
            analyticsAudienceTopAuthorsShareLimit: max(1, $this->intValue($audience, ['top_authors_share_limit'], 5)),
            analyticsAudienceMostActiveHoursLimit: max(1, $this->intValue($audience, ['most_active_hours_limit'], 3)),
            analyticsAudienceHourMin: $this->intValue($audience, ['hour_min'], 0),
            analyticsAudienceHourMax: $this->intValue($audience, ['hour_max'], 23),
            analyticsFraudPostRules: $this->arrayAt($fraud, ['post_rules']),
            analyticsFraudTriggerRules: $this->arrayAt($fraud, ['triggers']),
            analyticsFraudSuspiciousPostExcerptLength: max(1, $this->intValue($fraud, ['suspicious_post_excerpt_length'], 160)),
            analyticsFraudSuspiciousPostsLimit: max(1, $this->intValue($fraud, ['suspicious_posts_limit'], 5)),
            analyticsFraudRiskMaxScore: max(1, $this->intValue($fraud, ['risk_max_score'], 100)),
            analyticsFraudRiskMediumThreshold: $riskMedium,
            analyticsFraudRiskHighThreshold: max($riskMedium, $this->intValue($fraud, ['risk_high_threshold'], 60)),
            analyticsScoreProfiles: $this->normalizeScoreProfiles($scoreProfiles),
            searchMessagesLimitDefault: max(1, $this->intValue($search, ['messages_limit_default'], 20)),
            searchMessagesLimitMax: max(1, $this->intValue($search, ['messages_limit_max'], 100)),
            searchCommentsLimitDefault: max(1, $this->intValue($search, ['comments_limit_default'], 20)),
            searchCommentsLimitMax: max(1, $this->intValue($search, ['comments_limit_max'], 50)),
            parserCustomRangeMaxDays: max(1, $this->intValue($parser, ['custom_range_max_days'], 31)),
        );
    }

    /**
     * @param array<string, mixed> $scoreProfiles
     * @return array<string, array{views: float, forwards: float, replies: float, reactions: float, gifts: float}>
     */
    private function normalizeScoreProfiles(array $scoreProfiles): array
    {
        $defaults = $this->defaultScoreProfiles();
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
    private function defaultScoreProfiles(): array
    {
        return [
            'balanced' => ['views' => 1.0, 'forwards' => 5.0, 'replies' => 8.0, 'reactions' => 2.0, 'gifts' => 10.0],
            'reach' => ['views' => 3.0, 'forwards' => 4.0, 'replies' => 2.0, 'reactions' => 1.0, 'gifts' => 2.0],
            'discussion' => ['views' => 1.0, 'forwards' => 3.0, 'replies' => 12.0, 'reactions' => 2.0, 'gifts' => 3.0],
            'virality' => ['views' => 1.0, 'forwards' => 10.0, 'replies' => 6.0, 'reactions' => 3.0, 'gifts' => 5.0],
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     * @return array<string, mixed>
     */
    private function arrayAt(array $config, array $path): array
    {
        $value = $this->valueByPath($config, $path);

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private function intValue(array $config, array $path, int $default): int
    {
        $value = $this->valueByPath($config, $path);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $path
     */
    private function valueByPath(array $config, array $path): mixed
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
