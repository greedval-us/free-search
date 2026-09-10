<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\TelegramTrackingRequest;
use App\Models\TelegramTracking;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\Telegram\Tracking\TrackingConfig;
use App\Modules\Telegram\Tracking\TrackingLifecycle;
use App\Modules\Telegram\Tracking\TrackingPresenter;
use App\Modules\Telegram\Tracking\TrackingReports;
use App\Modules\Telegram\Tracking\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

final class TelegramTrackingController extends Controller
{
    public function __construct(private readonly TrackingConfig $config, private readonly TrackingService $service,
        private readonly TrackingReports $reports, private readonly TrackingPresenter $presenter) {}

    public function index(Request $request, TrackingLifecycle $lifecycle): JsonResponse
    {
        $lifecycle->synchronize($request->user()->id);
        $query = $this->reports->available($request->user()->id);
        $tasks = $query->with('sources')->withCount('messages')->latest('id')->simplePaginate($this->config->integer('list_page_size'));

        return response()->json(['ok' => true, 'items' => collect($tasks->items())->map($this->presenter->task(...)),
            'has_more' => $tasks->hasMorePages(), 'limit' => $this->config->limit($request->user()->currentPlan()),
            'active_count' => TelegramTracking::query()->forUser($request->user()->id)->active()->count(),
            'max_sources' => $this->config->integer('max_sources'), 'keyword_min_length' => $this->config->integer('keyword_min_length'),
            'interval_hours' => $this->config->interval(), 'max_interval_hours' => $this->config->integer('max_interval_hours'),
            'retention_days' => $this->config->integer('retention_days'), 'duration_months' => $this->config->integer('duration_months')]);
    }

    public function validateGroups(TelegramTrackingRequest $request, TrackingGateway $gateway): JsonResponse
    {
        $sources = $gateway->resolve($request->validated('groups'));

        return response()->json(['ok' => true, 'groups' => array_map(static fn ($source) => [
            'title' => $source['title'], 'peer_id' => $source['peer_id'],
        ], $sources)]);
    }

    public function store(TelegramTrackingRequest $request): JsonResponse
    {
        $task = $this->service->create($request->user(), $request->validated());

        return response()->json(['ok' => true, 'id' => $task->id], 201);
    }

    public function change(TelegramTrackingRequest $request, int $tracking): JsonResponse
    {
        $this->service->change($request->user(), $tracking, $request->validated('action'), $request->boolean('notify_bot'));

        return response()->json(['ok' => true]);
    }

    public function messages(Request $request, int $tracking): JsonResponse
    {
        $task = $this->reports->available($request->user()->id)->findOrFail($tracking);
        $items = $task->messages()->with('source')->latest('id')->simplePaginate($this->config->integer('message_page_size'));

        return response()->json(['ok' => true, 'items' => collect($items->items())->map(TrackingPresenter::message(...)), 'has_more' => $items->hasMorePages()]);
    }

    public function download(Request $request, int $tracking, string $format): Response
    {
        abort_unless(in_array($format, TrackingReports::FORMATS, true), 404);
        $locale = $request->query('locale', app()->getLocale());
        if (in_array($locale, ['ru', 'en'], true)) {
            app()->setLocale($locale);
        }
        $task = $this->reports->available($request->user()->id)->findOrFail($tracking);
        $filename = 'telegram-tracking-'.$task->id.'.'.$format;

        if ($format === 'xlsx') {
            $response = Excel::download($this->reports->workbook($task), $filename);
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        }

        return response()->streamDownload(function () use ($task): void {
            foreach ($this->reports->json($task) as $chunk) {
                echo $chunk;
            }
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
