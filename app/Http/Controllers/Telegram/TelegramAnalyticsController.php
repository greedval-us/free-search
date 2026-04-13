<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Requests\Telegram\TelegramAnalyticsRequest;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\TelegramAnalyticsRangeResolver;
use Carbon\Carbon;
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
        app()->setLocale($request->locale());

        $params = $request->toParamsDTO();
        $range = $this->rangeResolver->resolveRange($request);
        $reportData = $this->analyticsApplicationService->buildReport($params, $range['from'], $range['to']);
        $data = $reportData->report;
        $previousData = $reportData->previousReport;

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
}

