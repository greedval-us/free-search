<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\TelegramAnalyticsRequest;
use App\Modules\Telegram\Analytics\TelegramAnalyticsQueryService;
use App\Modules\Telegram\Analytics\TelegramAnalyticsRangeResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TelegramAnalyticsController extends Controller
{
    public function __construct(
        private readonly TelegramAnalyticsQueryService $analyticsQueryService,
        private readonly TelegramAnalyticsRangeResolver $rangeResolver,
    ) {
    }

    public function summary(TelegramAnalyticsRequest $request): JsonResponse
    {
        $params = [
            'chatUsername' => $request->chatUsername(),
            'scorePriority' => $request->scorePriority(),
            'keyword' => $request->keyword(),
        ];
        $range = $this->rangeResolver->resolveRange($request);
        $data = $this->analyticsQueryService->buildSummary(
            $params,
            $range['from'],
            $range['to'],
            (string) $request->query('snapshotRole', '')
        );

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    public function report(TelegramAnalyticsRequest $request): View|Response
    {
        app()->setLocale($request->locale());

        $params = [
            'chatUsername' => $request->chatUsername(),
            'scorePriority' => $request->scorePriority(),
            'keyword' => $request->keyword(),
        ];
        $range = $this->rangeResolver->resolveRange($request);
        $reportData = $this->analyticsQueryService->buildReport($params, $range['from'], $range['to']);
        $data = $reportData['report'];
        $previousData = $reportData['previousReport'];

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
