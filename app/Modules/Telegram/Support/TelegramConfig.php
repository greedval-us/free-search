<?php

namespace App\Modules\Telegram\Support;

final class TelegramConfig
{
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
        private readonly int $analyticsPeriodMinDays,
        private readonly int $analyticsPeriodMaxDays,
        private readonly int $analyticsCustomRangeMaxDays,
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
        private readonly int $searchMessagesLimitDefault,
        private readonly int $searchMessagesLimitMax,
        private readonly int $searchCommentsLimitDefault,
        private readonly int $searchCommentsLimitMax,
        private readonly int $parserCustomRangeMaxDays,
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

    public function analyticsPeriodMinDays(): int
    {
        return $this->analyticsPeriodMinDays;
    }

    public function analyticsCustomRangeMaxDays(): int
    {
        return $this->analyticsCustomRangeMaxDays;
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

    public function searchMessagesLimitDefault(): int
    {
        return $this->searchMessagesLimitDefault;
    }

    public function searchMessagesLimitMax(): int
    {
        return max($this->searchMessagesLimitDefault, $this->searchMessagesLimitMax);
    }

    public function searchCommentsLimitDefault(): int
    {
        return $this->searchCommentsLimitDefault;
    }

    public function searchCommentsLimitMax(): int
    {
        return max($this->searchCommentsLimitDefault, $this->searchCommentsLimitMax);
    }

    public function parserCustomRangeMaxDays(): int
    {
        return $this->parserCustomRangeMaxDays;
    }
}
