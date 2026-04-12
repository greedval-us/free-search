<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\TelegramAnalyticsRequest;
use App\Modules\Telegram\Analytics\TelegramAnalyticsService;
use App\Modules\Telegram\Analytics\TelegramAnalyticsSnapshotStore;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TelegramAnalyticsController extends Controller
{
    public function __construct(
        private readonly TelegramAnalyticsService $analyticsService,
        private readonly TelegramAnalyticsSnapshotStore $snapshotStore,
    ) {
    }

    public function summary(TelegramAnalyticsRequest $request): JsonResponse
    {
        $params = $this->requestParams($request);
        $range = $this->resolveRange($request);
        $data = $this->buildAnalytics($params, $range['from'], $range['to']);

        $this->snapshotStore->storeSummarySnapshot(
            $params['chatUsername'],
            $range['from'],
            $range['to'],
            $params['scorePriority'],
            $params['keyword'],
            $data
        );

        $snapshotRole = strtolower((string) $request->query('snapshotRole', ''));
        if (in_array($snapshotRole, ['current', 'previous'], true)) {
            $this->snapshotStore->storeNamedSnapshot($snapshotRole, $data);
        }

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    public function report(TelegramAnalyticsRequest $request): View|Response
    {
        app()->setLocale($request->locale());

        $params = $this->requestParams($request);
        $range = $this->resolveRange($request);
        $namedCurrent = $this->snapshotStore->getNamedSnapshot('current');
        $namedPrevious = $this->snapshotStore->getNamedSnapshot('previous');

        if ($this->snapshotStore->matchesRequest(
            $namedCurrent,
            $params['chatUsername'],
            $params['scorePriority'],
            $params['keyword']
        )) {
            $data = $namedCurrent;
            $previousData = $this->snapshotStore->matchesRequest(
                $namedPrevious,
                $params['chatUsername'],
                $params['scorePriority'],
                $params['keyword']
            )
                ? $namedPrevious
                : null;
        } else {
            $data = null;
            $previousData = null;
        }

        if (!is_array($data)) {
            $data = $this->snapshotStore->getSummarySnapshot(
                $params['chatUsername'],
                $range['from'],
                $range['to'],
                $params['scorePriority'],
                $params['keyword']
            ) ?? $this->buildAnalytics($params, $range['from'], $range['to']);
        }
        $this->snapshotStore->storeSummarySnapshot(
            $params['chatUsername'],
            $range['from'],
            $range['to'],
            $params['scorePriority'],
            $params['keyword'],
            $data
        );

        if (!is_array($previousData)) {
            $previousRange = $this->resolvePreviousRangeForReport($data, $range['from'], $range['to']);
            $previousData = $this->snapshotStore->getSummarySnapshot(
                $params['chatUsername'],
                $previousRange['from'],
                $previousRange['to'],
                $params['scorePriority'],
                $params['keyword']
            ) ?? $this->buildAnalytics($params, $previousRange['from'], $previousRange['to']);
            $this->snapshotStore->storeSummarySnapshot(
                $params['chatUsername'],
                $previousRange['from'],
                $previousRange['to'],
                $params['scorePriority'],
                $params['keyword'],
                $previousData
            );
        }

        $viewData = [
            'report' => $data,
            'previousReport' => $previousData,
            'locale' => app()->getLocale(),
            'generatedAt' => Carbon::now(config('app.timezone'))->format('d.m.Y H:i'),
        ];

        if ($request->boolean('download')) {
            $filename = sprintf(
                'telegram-analytics-%s-%s.html',
                preg_replace('/[^a-z0-9_-]+/i', '-', $request->chatUsername()) ?: 'report',
                Carbon::now(config('app.timezone'))->format('Ymd-His')
            );

            return response()
                ->view('reports.telegram.analytics', $viewData)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        return view('reports.telegram.analytics', $viewData);
    }

    /**
     * @return array{chatUsername: string, scorePriority: string, keyword: ?string}
     */
    private function requestParams(TelegramAnalyticsRequest $request): array
    {
        return [
            'chatUsername' => $request->chatUsername(),
            'scorePriority' => $request->scorePriority(),
            'keyword' => $request->keyword(),
        ];
    }

    /**
     * @param array{chatUsername: string, scorePriority: string, keyword: ?string} $params
     * @return array<string, mixed>
     */
    private function buildAnalytics(array $params, Carbon $from, Carbon $to): array
    {
        return $this->analyticsService->build(
            $params['chatUsername'],
            $from,
            $to,
            $params['scorePriority'],
            $params['keyword']
        );
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    private function resolveRange(TelegramAnalyticsRequest $request): array
    {
        if ($request->customRange()) {
            return [
                'from' => $request->dateFrom()->copy(),
                'to' => $request->dateTo()->copy(),
            ];
        }

        $to = Carbon::now(config('app.timezone'))->endOfDay();
        $from = $to->copy()->subDays($request->periodDays() - 1)->startOfDay();

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    private function resolvePreviousRange(Carbon $from, Carbon $to): array
    {
        $normalizedFrom = $from->copy()->startOfSecond();
        $normalizedTo = $to->copy()->startOfSecond();
        $spanSeconds = max(0, $normalizedTo->diffInSeconds($normalizedFrom));

        $previousTo = $normalizedFrom->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subSeconds($spanSeconds);

        return [
            'from' => $previousFrom,
            'to' => $previousTo,
        ];
    }

    /**
     * @param array<string, mixed> $report
     * @return array{from: Carbon, to: Carbon}
     */
    private function resolvePreviousRangeForReport(array $report, Carbon $fallbackFrom, Carbon $fallbackTo): array
    {
        $currentFromIso = data_get($report, 'range.dateFrom');
        $currentToIso = data_get($report, 'range.dateTo');

        if (!is_string($currentFromIso) || !is_string($currentToIso)) {
            return $this->resolvePreviousRange($fallbackFrom, $fallbackTo);
        }

        try {
            $currentFromUtc = Carbon::parse($currentFromIso)->utc();
            $currentToUtc = Carbon::parse($currentToIso)->utc();
            $spanSeconds = max(0, $currentToUtc->diffInSeconds($currentFromUtc));

            $previousToUtc = $currentFromUtc->copy()->subSecond();
            $previousFromUtc = $previousToUtc->copy()->subSeconds($spanSeconds);

            // Keep report previous period aligned with frontend date-only summary query.
            $previousDateFrom = $previousFromUtc->format('Y-m-d');
            $previousDateTo = $previousToUtc->format('Y-m-d');
            $timezone = config('app.timezone');

            return [
                'from' => Carbon::createFromFormat('Y-m-d', $previousDateFrom, $timezone)->startOfDay(),
                'to' => Carbon::createFromFormat('Y-m-d', $previousDateTo, $timezone)->endOfDay(),
            ];
        } catch (\Throwable) {
            return $this->resolvePreviousRange($fallbackFrom, $fallbackTo);
        }
    }
}
