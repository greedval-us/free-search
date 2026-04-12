<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\TelegramAnalyticsRequest;
use App\Modules\Telegram\Analytics\TelegramAnalyticsRangeResolver;
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
        private readonly TelegramAnalyticsRangeResolver $rangeResolver,
        private readonly TelegramAnalyticsSnapshotStore $snapshotStore,
    ) {
    }

    public function summary(TelegramAnalyticsRequest $request): JsonResponse
    {
        $params = $this->requestParams($request);
        $range = $this->rangeResolver->resolveRange($request);
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
        $range = $this->rangeResolver->resolveRange($request);
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
            $previousRange = $this->rangeResolver->resolvePreviousRangeForReport($data, $range['from'], $range['to']);
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
}
