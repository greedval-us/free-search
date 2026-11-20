<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeAnalyticsReportScheduleRequest;
use App\Http\Responses\ScheduledAnalyticsReportHistory;
use App\Integrations\TelegramBot\ReportDeliveryStatus;
use App\Models\YouTubeAnalyticsReport;
use App\Models\YouTubeAnalyticsSchedule;
use App\Modules\YouTube\Analytics\Reports\AnalyticsReportScheduleService;
use App\Support\Http\DocumentResponseHeaders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class YouTubeAnalyticsReportsController extends Controller
{
    public function __construct(private readonly AnalyticsReportScheduleService $schedules, private readonly ReportDeliveryStatus $deliveryStatus) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $schedules = YouTubeAnalyticsSchedule::query()->forUser($userId)->latest('id')->get();
        $reports = YouTubeAnalyticsReport::query()->historyForUser($userId)
            ->paginate(max(1, (int) config('youtube_analytics_reports.list_page_size', 20)));

        return $this->jsonData([
            'schedules' => $schedules->map($this->schedulePayload(...)),
            'reports' => ScheduledAnalyticsReportHistory::page($reports, 'youtube_analytics_reports'),
            ...$this->deliveryStatus->forUser($userId),
            'timezone' => (string) config('youtube_analytics_reports.timezone', 'Europe/Moscow'),
            'maxChannels' => max(1, (int) config('youtube_analytics_reports.max_channels', 3)),
            'maxSchedules' => max(1, (int) config('youtube_analytics_reports.max_schedules', 5)),
        ]);
    }

    public function store(YouTubeAnalyticsReportScheduleRequest $request): JsonResponse
    {
        $schedule = $this->schedules->create($request->user(), $request->scheduleData());

        return $this->jsonData($this->schedulePayload($schedule), 201);
    }

    public function change(YouTubeAnalyticsReportScheduleRequest $request, int $schedule): JsonResponse
    {
        $this->schedules->change($request->user(), $schedule, $request->validated('action'));

        return $this->jsonOk();
    }

    public function runNow(Request $request, int $schedule): JsonResponse
    {
        $this->schedules->runNow($request->user(), $schedule);

        return $this->jsonOk([], 202);
    }

    public function destroy(Request $request, int $schedule): JsonResponse
    {
        $this->schedules->destroy($request->user(), $schedule);

        return $this->jsonOk();
    }

    public function view(Request $request, int $report): View|Response
    {
        return $this->html($request, $this->completedReport($request, $report), false);
    }

    public function download(Request $request, int $report, string $format): View|Response|JsonResponse
    {
        abort_unless(in_array($format, ['html', 'json'], true), 404);
        $saved = $this->completedReport($request, $report);
        if ($format === 'html') {
            return $this->html($request, $saved, true);
        }

        $response = response()->json($saved->data, 200, DocumentResponseHeaders::download(), JSON_UNESCAPED_UNICODE);

        return $response->header('Content-Disposition', $response->headers->makeDisposition('attachment', 'youtube-analytics-'.$saved->id.'.json'));
    }

    private function completedReport(Request $request, int $id): YouTubeAnalyticsReport
    {
        return YouTubeAnalyticsReport::query()->forUser($request->user()->id)
            ->where('status', YouTubeAnalyticsReport::COMPLETED)->whereNotNull('data')->findOrFail($id);
    }

    private function html(Request $request, YouTubeAnalyticsReport $saved, bool $download): View|Response
    {
        $locale = $request->query('locale', app()->getLocale());

        return $this->localizedHtmlReportResponse(
            locale: in_array($locale, ['ru', 'en'], true) ? $locale : app()->getLocale(),
            view: 'reports.youtube.analytics', report: $saved->data,
            extraViewData: [
                'previousReport' => $saved->data['previousReport'] ?? [],
                'generatedAt' => $saved->completed_at?->setTimezone($saved->schedule?->timezone ?? (string) config('youtube_analytics_reports.timezone', 'Europe/Moscow'))->format('d.m.Y H:i'),
            ],
            download: $download, filenamePrefix: 'youtube-analytics-'.$saved->id,
            filenameTarget: $saved->channel_input,
        );
    }

    private function schedulePayload(YouTubeAnalyticsSchedule $schedule): array
    {
        return [
            'id' => $schedule->id, 'name' => $schedule->name, 'channels' => $schedule->channels,
            'interval' => $schedule->interval, 'sendTime' => $schedule->send_time, 'timezone' => $schedule->timezone,
            'sendToBot' => $schedule->send_to_bot, 'enabled' => $schedule->enabled,
            'nextRunAt' => $schedule->next_run_at?->toIso8601String(), 'createdAt' => $schedule->created_at?->toIso8601String(),
        ];
    }
}
