<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\TelegramAnalyticsRequest;
use App\Modules\Telegram\Analytics\TelegramAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TelegramAnalyticsController extends Controller
{
    public function __construct(
        private readonly TelegramAnalyticsService $analyticsService,
    ) {
    }

    public function summary(TelegramAnalyticsRequest $request): JsonResponse
    {
        $range = $this->resolveRange($request);

        return response()->json([
            'ok' => true,
            'data' => $this->analyticsService->build(
                $request->chatUsername(),
                $range['from'],
                $range['to'],
                $request->scorePriority(),
                $request->keyword()
            ),
        ]);
    }

    public function report(TelegramAnalyticsRequest $request): View|Response
    {
        app()->setLocale($request->locale());

        $range = $this->resolveRange($request);
        $data = $this->analyticsService->build(
            $request->chatUsername(),
            $range['from'],
            $range['to'],
            $request->scorePriority(),
            $request->keyword()
        );

        $viewData = [
            'report' => $data,
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
}
