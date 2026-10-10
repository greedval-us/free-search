<?php

namespace App\Http\Controllers\NewsMediaIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsMediaIntel\NewsMediaReportScheduleRequest;
use App\Integrations\TelegramBot\ReportDeliveryStatus;
use App\Models\NewsMediaReportSchedule;
use App\Models\NewsMediaScheduledReport;
use App\Modules\NewsMediaIntel\Application\Reports\ReportConfig;
use App\Modules\NewsMediaIntel\Application\Reports\ReportScheduleService;
use App\Support\Http\DocumentResponseHeaders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class NewsMediaReportsController extends Controller
{
    public function __construct(private readonly ReportScheduleService $schedules, private readonly ReportDeliveryStatus $deliveryStatus) {}

    public function index(Request $request, ReportConfig $config): JsonResponse
    {
        $userId = $request->user()->id;
        $schedules = NewsMediaReportSchedule::query()->forUser($userId)->latest('id')->get();
        $reports = NewsMediaScheduledReport::query()->forUser($userId)->with('schedule')
            ->select(['id', 'schedule_id', 'user_id', 'query', 'scheduled_for', 'status', 'error_code', 'completed_at'])
            ->latest('id')->paginate(max(1, (int) config('news_media_reports.list_page_size', 20)));

        return $this->jsonData([
            'schedules' => $schedules->map($this->schedulePayload(...)),
            'reports' => ['data' => collect($reports->items())->map($this->reportPayload(...)),
                'currentPage' => $reports->currentPage(), 'lastPage' => $reports->lastPage(),
                'total' => $reports->total(), 'perPage' => $reports->perPage()],
            ...$this->deliveryStatus->forUser($userId),
            'timezone' => (string) config('news_media_reports.timezone', 'Europe/Moscow'),
            'maxQueries' => $config->maxQueries(),
            'maxSchedules' => max(1, (int) config('news_media_reports.max_schedules', 5)),
        ]);
    }

    public function store(NewsMediaReportScheduleRequest $request): JsonResponse
    {
        return $this->jsonData($this->schedulePayload($this->schedules->create($request->user(), $request->scheduleData())), 201);
    }

    public function change(NewsMediaReportScheduleRequest $request, int $schedule): JsonResponse
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

        return $response->header('Content-Disposition', $response->headers->makeDisposition('attachment', 'news-media-report-'.$saved->id.'.json'));
    }

    private function completedReport(Request $request, int $id): NewsMediaScheduledReport
    {
        return NewsMediaScheduledReport::query()->forUser($request->user()->id)
            ->where('status', NewsMediaScheduledReport::COMPLETED)->whereNotNull('data')->findOrFail($id);
    }

    private function html(Request $request, NewsMediaScheduledReport $saved, bool $download): View|Response
    {
        $locale = $request->query('locale', app()->getLocale());

        return $this->localizedHtmlReportResponse(
            locale: in_array($locale, ['ru', 'en'], true) ? $locale : app()->getLocale(),
            view: 'reports.news-media-intel.analytics', report: $saved->data,
            download: $download, filenamePrefix: 'news-media-report-'.$saved->id, filenameTarget: $saved->query,
        );
    }

    private function schedulePayload(NewsMediaReportSchedule $schedule): array
    {
        return ['id' => $schedule->id, 'name' => $schedule->name, 'queries' => $schedule->queries,
            'brand' => $schedule->brand, 'competitors' => $schedule->competitors, 'domain' => $schedule->domain,
            'searchOptions' => $schedule->search_options, 'interval' => $schedule->interval,
            'sendTime' => $schedule->send_time, 'timezone' => $schedule->timezone,
            'sendToBot' => $schedule->send_to_bot, 'enabled' => $schedule->enabled,
            'nextRunAt' => $schedule->next_run_at?->toIso8601String(), 'createdAt' => $schedule->created_at?->toIso8601String()];
    }

    private function reportPayload(NewsMediaScheduledReport $report): array
    {
        $reason = in_array($report->error_code, [
            'account_unavailable', 'disabled', 'generation_failed', 'attempts_exhausted', 'invalid_options', 'invalid_queries',
        ], true) ? $report->error_code : 'generation_failed';

        return ['id' => $report->id, 'scheduleId' => $report->schedule_id, 'scheduleName' => $report->schedule?->name,
            'query' => $report->query, 'scheduledFor' => $report->scheduled_for?->toIso8601String(),
            'status' => $report->status, 'errorCode' => $report->error_code,
            'errorMessage' => $report->error_code === null ? null : __('news_media_reports.errors.'.$reason),
            'completedAt' => $report->completed_at?->toIso8601String()];
    }
}
