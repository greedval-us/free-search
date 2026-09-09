<?php

namespace App\Modules\Telegram\Analytics;

use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsRangeResolverInterface;
use App\Modules\Telegram\DTO\Request\TelegramAnalyticsParamsDTO;
use App\Modules\Telegram\DTO\Result\AnalyticsReportResultDTO;
use App\Modules\Telegram\DTO\Result\AnalyticsSummaryResultDTO;
use App\Modules\Telegram\Support\TelegramConfig;
use App\Support\Reports\ReportSnapshotStore;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramAnalyticsApplicationService implements TelegramAnalyticsApplicationServiceInterface
{
    public function __construct(
        private readonly TelegramAnalyticsService $analyticsService,
        private readonly TelegramAnalyticsRangeResolverInterface $rangeResolver,
        private readonly ReportSnapshotStore $snapshotStore,
        private readonly TelegramConfig $config,
    ) {}

    public function buildSummary(
        int $userId,
        TelegramAnalyticsParamsDTO $params,
        Carbon $from,
        Carbon $to,
    ): AnalyticsSummaryResultDTO {
        $data = $this->loadCachedAnalytics($params, $from, $to);
        $previousRange = $this->rangeResolver->resolvePreviousRange($from, $to);
        $previous = [];
        try {
            $previous = $this->loadCachedAnalytics($params, $previousRange['from'], $previousRange['to']);
        } catch (Throwable $exception) {
            Log::warning('Telegram comparison unavailable; current summary preserved.', ['exception' => $exception::class]);
        }

        $this->snapshotStore->store($userId, 'telegram.analytics', $this->reportParameters($params, $from, $to), [
            'report' => $data,
            'previousReport' => $previous,
        ]);

        return new AnalyticsSummaryResultDTO([...$data, 'previousReport' => $previous !== [] ? $previous : null]);
    }

    public function buildReport(int $userId, TelegramAnalyticsParamsDTO $params, Carbon $from, Carbon $to): AnalyticsReportResultDTO
    {
        $snapshot = $this->snapshotStore->get($userId, 'telegram.analytics', $this->reportParameters($params, $from, $to));

        return new AnalyticsReportResultDTO($snapshot['report'], $snapshot['previousReport']);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadCachedAnalytics(TelegramAnalyticsParamsDTO $params, Carbon $from, Carbon $to): array
    {
        return Cache::remember(
            $this->analyticsCacheKey($params, $from, $to),
            now()->addSeconds($this->summaryCacheTtlSeconds()),
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
     * @return array<string, mixed>
     */
    private function reportParameters(TelegramAnalyticsParamsDTO $params, Carbon $from, Carbon $to): array
    {
        return [...get_object_vars($params), 'from' => $from->toIso8601String(), 'to' => $to->toIso8601String()];
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

    private function summaryCacheTtlSeconds(): int
    {
        return $this->config->analyticsSummaryCacheTtlSeconds();
    }
}
