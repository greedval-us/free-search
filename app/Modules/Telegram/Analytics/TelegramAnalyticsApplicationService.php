<?php

namespace App\Modules\Telegram\Analytics;

use App\Modules\Telegram\DTO\Request\TelegramAnalyticsParamsDTO;
use App\Modules\Telegram\DTO\Result\AnalyticsReportResultDTO;
use App\Modules\Telegram\DTO\Result\AnalyticsSummaryResultDTO;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TelegramAnalyticsApplicationService implements TelegramAnalyticsApplicationServiceInterface
{
    private const SUMMARY_CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly TelegramAnalyticsService $analyticsService,
        private readonly TelegramAnalyticsRangeResolver $rangeResolver,
        private readonly TelegramAnalyticsSnapshotStore $snapshotStore,
    ) {
    }

    public function buildSummary(
        TelegramAnalyticsParamsDTO $params,
        Carbon $from,
        Carbon $to,
        ?string $snapshotRole = null
    ): AnalyticsSummaryResultDTO
    {
        $data = $this->loadCachedAnalytics($params, $from, $to);
        $this->storeSummarySnapshot($params, $from, $to, $data);

        $snapshotRole = strtolower(trim((string) $snapshotRole));
        if (in_array($snapshotRole, ['current', 'previous'], true)) {
            $this->snapshotStore->storeNamedSnapshot($snapshotRole, $data);
        }

        return new AnalyticsSummaryResultDTO($data);
    }

    public function buildReport(TelegramAnalyticsParamsDTO $params, Carbon $from, Carbon $to): AnalyticsReportResultDTO
    {
        $namedCurrent = $this->snapshotStore->getNamedSnapshot('current');
        $namedPrevious = $this->snapshotStore->getNamedSnapshot('previous');

        $matchesCurrent = $this->snapshotStore->matchesRequest(
            $namedCurrent,
            $params->chatUsername,
            $params->scorePriority,
            $params->keyword
        );

        $data = $matchesCurrent ? $namedCurrent : null;
        $previousData = $matchesCurrent && $this->snapshotStore->matchesRequest(
            $namedPrevious,
            $params->chatUsername,
            $params->scorePriority,
            $params->keyword
        )
            ? $namedPrevious
            : null;

        if (!is_array($data)) {
            $data = $this->snapshotStore->getSummarySnapshot(
                $params->chatUsername,
                $from,
                $to,
                $params->scorePriority,
                $params->keyword
            ) ?? $this->loadCachedAnalytics($params, $from, $to);
        }
        $this->storeSummarySnapshot($params, $from, $to, $data);

        if (!is_array($previousData)) {
            $previousRange = $this->rangeResolver->resolvePreviousRangeForReport($data, $from, $to);
            $previousData = $this->snapshotStore->getSummarySnapshot(
                $params->chatUsername,
                $previousRange['from'],
                $previousRange['to'],
                $params->scorePriority,
                $params->keyword
            ) ?? $this->loadCachedAnalytics($params, $previousRange['from'], $previousRange['to']);
            $this->storeSummarySnapshot($params, $previousRange['from'], $previousRange['to'], $previousData);
        }

        return new AnalyticsReportResultDTO($data, $previousData);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadCachedAnalytics(TelegramAnalyticsParamsDTO $params, Carbon $from, Carbon $to): array
    {
        return Cache::remember(
            $this->analyticsCacheKey($params, $from, $to),
            now()->addSeconds(self::SUMMARY_CACHE_TTL_SECONDS),
            fn (): array => $this->analyticsService->build(
                $params->chatUsername,
                $from,
                $to,
                $params->scorePriority,
                $params->keyword
            )
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function storeSummarySnapshot(TelegramAnalyticsParamsDTO $params, Carbon $from, Carbon $to, array $data): void
    {
        $this->snapshotStore->storeSummarySnapshot(
            $params->chatUsername,
            $from,
            $to,
            $params->scorePriority,
            $params->keyword,
            $data
        );
    }

    private function analyticsCacheKey(TelegramAnalyticsParamsDTO $params, Carbon $from, Carbon $to): string
    {
        $chatUsername = strtolower(trim(ltrim($params->chatUsername, '@')));
        $priority = strtolower(trim($params->scorePriority));
        $keyword = trim((string) ($params->keyword ?? ''));

        return implode(':', [
            'telegram_analytics_summary_v1',
            $chatUsername,
            $priority,
            sha1($keyword),
            $from->copy()->utc()->toIso8601String(),
            $to->copy()->utc()->toIso8601String(),
        ]);
    }
}


