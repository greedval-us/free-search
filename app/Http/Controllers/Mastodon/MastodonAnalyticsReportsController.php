<?php

namespace App\Http\Controllers\Mastodon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mastodon\MastodonAnalyticsReportScheduleRequest;
use App\Models\MastodonAnalyticsReport;
use App\Models\MastodonAnalyticsSchedule;
use App\Modules\Mastodon\Analytics\Reports\AnalyticsReportScheduleService;
use App\Modules\TelegramBot\Application\BotAccess;
use App\Modules\TelegramBot\Models\BotLink;
use App\Support\Http\DocumentResponseHeaders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class MastodonAnalyticsReportsController extends Controller
{
    public function __construct(private readonly AnalyticsReportScheduleService $schedules) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $schedules = MastodonAnalyticsSchedule::query()->forUser($userId)->latest('id')->get();
        $reports = MastodonAnalyticsReport::query()->forUser($userId)->with('schedule')
            ->select(['id', 'schedule_id', 'user_id', 'account_input', 'scheduled_for', 'date_from', 'date_to', 'status', 'error_code', 'completed_at'])
            ->latest('id')->paginate(max(1, (int) config('mastodon_analytics_reports.list_page_size', 20)));
        $link = BotLink::query()->where('user_id', $userId)->first();

        return $this->jsonData([
            'schedules' => $schedules->map($this->schedulePayload(...)),
            'reports' => [
                'data' => collect($reports->items())->map($this->reportPayload(...)),
                'currentPage' => $reports->currentPage(), 'lastPage' => $reports->lastPage(),
                'total' => $reports->total(), 'perPage' => $reports->perPage(),
            ],
            'botLinked' => app(BotAccess::class)->allows($link),
            'botExportsEnabled' => $link?->exports_enabled ?? false,
            'timezone' => (string) config('mastodon_analytics_reports.timezone', 'Europe/Moscow'),
            'maxAccounts' => max(1, (int) config('mastodon_analytics_reports.max_accounts', 3)),
            'maxSchedules' => max(1, (int) config('mastodon_analytics_reports.max_schedules', 5)),
        ]);
    }

    public function store(MastodonAnalyticsReportScheduleRequest $request): JsonResponse
    {
        $schedule = $this->schedules->create($request->user(), $request->scheduleData());

        return $this->jsonData($this->schedulePayload($schedule), 201);
    }

    public function change(MastodonAnalyticsReportScheduleRequest $request, int $schedule): JsonResponse
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

        return $response->header('Content-Disposition', $response->headers->makeDisposition('attachment', 'mastodon-analytics-'.$saved->id.'.json'));
    }

    private function completedReport(Request $request, int $id): MastodonAnalyticsReport
    {
        return MastodonAnalyticsReport::query()->forUser($request->user()->id)
            ->where('status', MastodonAnalyticsReport::COMPLETED)->whereNotNull('data')->findOrFail($id);
    }

    private function html(Request $request, MastodonAnalyticsReport $saved, bool $download): View|Response
    {
        $locale = $request->query('locale', app()->getLocale());

        return $this->localizedHtmlReportResponse(
            locale: in_array($locale, ['ru', 'en'], true) ? $locale : app()->getLocale(),
            view: 'reports.mastodon.analytics', report: $saved->data,
            extraViewData: [
                'previousReport' => $saved->data['previousReport'] ?? [],
                'generatedAt' => $saved->completed_at?->setTimezone($saved->schedule?->timezone ?? (string) config('mastodon_analytics_reports.timezone', 'Europe/Moscow'))->format('d.m.Y H:i'),
            ],
            download: $download, filenamePrefix: 'mastodon-analytics-'.$saved->id,
            filenameTarget: $saved->account_input,
        );
    }

    private function schedulePayload(MastodonAnalyticsSchedule $schedule): array
    {
        return [
            'id' => $schedule->id, 'name' => $schedule->name, 'accounts' => $schedule->accounts,
            'interval' => $schedule->interval, 'sendTime' => $schedule->send_time, 'timezone' => $schedule->timezone,
            'sendToBot' => $schedule->send_to_bot, 'enabled' => $schedule->enabled,
            'nextRunAt' => $schedule->next_run_at?->toIso8601String(), 'createdAt' => $schedule->created_at?->toIso8601String(),
        ];
    }

    private function reportPayload(MastodonAnalyticsReport $report): array
    {
        $timezone = $report->schedule?->timezone ?? (string) config('mastodon_analytics_reports.timezone', 'Europe/Moscow');

        return [
            'id' => $report->id, 'scheduleId' => $report->schedule_id, 'scheduleName' => $report->schedule?->name,
            'accountInput' => $report->account_input, 'scheduledFor' => $report->scheduled_for?->toIso8601String(),
            'dateFrom' => $report->date_from?->setTimezone($timezone)->toIso8601String(), 'dateTo' => $report->date_to?->setTimezone($timezone)->toIso8601String(),
            'status' => $report->status, 'errorCode' => $report->error_code,
            'errorMessage' => $report->error_code === null ? null : __('mastodon_analytics_reports.errors.'.(in_array($report->error_code, [
                'account_unavailable', 'access_denied', 'disabled', 'generation_failed', 'attempts_exhausted', 'collection_limit', 'pagination_stalled',
            ], true) ? $report->error_code : 'generation_failed')),
            'completedAt' => $report->completed_at?->toIso8601String(),
        ];
    }
}
