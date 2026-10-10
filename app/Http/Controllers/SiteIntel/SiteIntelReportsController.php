<?php

namespace App\Http\Controllers\SiteIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteIntel\SiteIntelReportScheduleRequest;
use App\Integrations\TelegramBot\ReportDeliveryStatus;
use App\Models\SiteIntelReportSchedule;
use App\Models\SiteIntelScheduledReport;
use App\Modules\SiteIntel\Application\Reports\ReportScheduleService;
use App\Services\Access\SiteIntelReportAccess;
use App\Support\Http\DocumentResponseHeaders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class SiteIntelReportsController extends Controller
{
    public function __construct(private readonly ReportScheduleService $schedules, private readonly SiteIntelReportAccess $access, private readonly ReportDeliveryStatus $deliveryStatus) {}

    public function index(Request $request): JsonResponse
    {
        $this->access->assertAny($request->user());
        $userId = $request->user()->id;
        $types = $this->access->availableTypes($request->user());
        $schedules = SiteIntelReportSchedule::query()->forUser($userId)->latest('id')->get();
        $reports = SiteIntelScheduledReport::query()->forUser($userId)->whereIn('report_type', $types)->with('schedule')
            ->select(['id', 'schedule_id', 'user_id', 'target_url', 'report_type', 'scheduled_for', 'status', 'error_code', 'completed_at'])
            ->latest('id')->paginate(max(1, (int) config('site_intel_reports.list_page_size', 20)));

        return $this->jsonData([
            'schedules' => $schedules->map($this->schedulePayload(...)),
            'reports' => ['data' => collect($reports->items())->map($this->reportPayload(...)),
                'currentPage' => $reports->currentPage(), 'lastPage' => $reports->lastPage(), 'total' => $reports->total(), 'perPage' => $reports->perPage()],
            'availableReportTypes' => $types,
            ...$this->deliveryStatus->forUser($userId),
            'timezone' => (string) config('site_intel_reports.timezone', 'Europe/Moscow'),
            'maxTargets' => max(1, (int) config('site_intel_reports.max_targets', 3)),
            'maxSchedules' => max(1, (int) config('site_intel_reports.max_schedules', 5)),
        ]);
    }

    public function store(SiteIntelReportScheduleRequest $request): JsonResponse
    {
        $this->access->ensure($request->user(), $request->validated('reportType'));

        return $this->jsonData($this->schedulePayload($this->schedules->create($request->user(), $request->scheduleData())), 201);
    }

    public function change(SiteIntelReportScheduleRequest $request, int $schedule): JsonResponse
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

        return $response->header('Content-Disposition', $response->headers->makeDisposition('attachment', 'site-intel-'.$saved->report_type.'-'.$saved->id.'.json'));
    }

    private function completedReport(Request $request, int $id): SiteIntelScheduledReport
    {
        $report = SiteIntelScheduledReport::query()->forUser($request->user()->id)
            ->where('status', SiteIntelScheduledReport::COMPLETED)->whereNotNull('data')->findOrFail($id);
        $this->access->ensure($request->user(), $report->report_type);

        return $report;
    }

    private function html(Request $request, SiteIntelScheduledReport $saved, bool $download): View|Response
    {
        $locale = $request->query('locale', app()->getLocale());

        return $this->localizedHtmlReportResponse(
            locale: in_array($locale, ['ru', 'en'], true) ? $locale : app()->getLocale(),
            view: $saved->report_type === 'seo-audit' ? 'reports.site-intel.seo-audit' : 'reports.site-intel.analytics', report: $saved->data,
            extraViewData: ['generatedAt' => $saved->completed_at?->setTimezone($saved->data['reportSchedule']['timezone'] ?? $saved->schedule?->timezone ?? 'Europe/Moscow')->format('d.m.Y H:i')],
            download: $download, filenamePrefix: 'site-intel-'.$saved->report_type.'-'.$saved->id,
            filenameTarget: (string) parse_url($saved->target_url, PHP_URL_HOST),
        );
    }

    private function schedulePayload(SiteIntelReportSchedule $schedule): array
    {
        return ['id' => $schedule->id, 'name' => $schedule->name, 'targets' => $schedule->targets,
            'reportType' => $schedule->report_type, 'crawlLimit' => $schedule->crawl_limit, 'platformType' => $schedule->platform_type,
            'interval' => $schedule->interval, 'sendTime' => $schedule->send_time, 'timezone' => $schedule->timezone,
            'sendToBot' => $schedule->send_to_bot, 'enabled' => $schedule->enabled,
            'nextRunAt' => $schedule->next_run_at?->toIso8601String(), 'createdAt' => $schedule->created_at?->toIso8601String()];
    }

    private function reportPayload(SiteIntelScheduledReport $report): array
    {
        return ['id' => $report->id, 'scheduleId' => $report->schedule_id, 'scheduleName' => $report->schedule?->name,
            'targetUrl' => $report->target_url, 'reportType' => $report->report_type, 'scheduledFor' => $report->scheduled_for?->toIso8601String(),
            'status' => $report->status, 'errorCode' => $report->error_code,
            'errorMessage' => $report->error_code === null ? null : __('site_intel_reports.errors.'.(in_array($report->error_code, [
                'account_unavailable', 'access_denied', 'disabled', 'generation_failed', 'attempts_exhausted', 'invalid_target',
            ], true) ? $report->error_code : 'generation_failed')),
            'completedAt' => $report->completed_at?->toIso8601String()];
    }
}
