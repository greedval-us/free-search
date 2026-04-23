<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Requests\Telegram\TelegramAnalyticsRequest;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\TelegramAnalyticsRangeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TelegramAnalyticsController extends BaseTelegramController
{
    public function __construct(
        private readonly TelegramAnalyticsApplicationServiceInterface $analyticsApplicationService,
        private readonly TelegramAnalyticsRangeResolver $rangeResolver,
    ) {
    }

    public function summary(TelegramAnalyticsRequest $request): JsonResponse
    {
        $params = $request->toParamsDTO();
        $range = $this->rangeResolver->resolveRange($request);
        $data = $this->analyticsApplicationService->buildSummary(
            $params,
            $range['from'],
            $range['to'],
            (string) $request->query('snapshotRole', '')
        );

        return $this->jsonOk(['data' => $data->data]);
    }

    public function report(TelegramAnalyticsRequest $request): View|Response
    {
        $this->applyRequestLocale($request->locale());

        $params = $request->toParamsDTO();
        $range = $this->rangeResolver->resolveRange($request);
        $reportData = $this->analyticsApplicationService->buildReport($params, $range['from'], $range['to']);
        $data = $reportData->report;
        $previousData = $reportData->previousReport;

        return $this->htmlReportResponse(
            view: 'reports.telegram.analytics',
            viewData: $this->reportViewData($data, [
                'previousReport' => $previousData,
            ]),
            download: $request->boolean('download'),
            filenamePrefix: 'telegram-analytics',
            filenameTarget: $request->chatUsername(),
        );
    }
}
