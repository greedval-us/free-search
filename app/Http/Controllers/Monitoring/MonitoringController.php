<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitoring\MonitoringRequest;
use App\Models\MonitoringProject;
use App\Models\MonitoringReport;
use App\Modules\TelegramBot\Models\BotLink;
use App\Services\Monitoring\MonitoringAccess;
use App\Services\Monitoring\MonitoringExports;
use App\Services\Monitoring\MonitoringManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class MonitoringController extends Controller
{
    public function __construct(private readonly MonitoringManager $manager) {}

    public function index(Request $request): Response
    {
        $this->requireUser($request);
        $projects = MonitoringProject::query()->where('user_id', $request->user()->id)
            ->withCount(['sources' => fn (Builder $query) => $query->where('status', '!=', 'removed'), 'reports'])
            ->latest('id')->paginate(20)->withQueryString();

        return Inertia::render('monitoring/Index', ['projects' => $projects,
            'defaultTimezone' => config('monitoring.timezone', config('app.timezone', 'UTC'))]);
    }

    public function store(MonitoringRequest $request): JsonResponse
    {
        $project = DB::transaction(function () use ($request): MonitoringProject {
            $project = $this->manager->create($request->user(), $request->validated());
            $this->manager->saveSchedule($project, ['period' => 'day', 'enabled' => true,
                'time' => '09:00', 'timezone' => $project->timezone, 'delivery_enabled' => $project->delivery_enabled]);

            return $project;
        });

        return response()->json(['ok' => true, 'id' => $project->id], 201);
    }

    public function show(Request $request, int $project): Response
    {
        return Inertia::render('monitoring/Project', $this->projectState($request, $project));
    }

    public function status(Request $request, int $project): JsonResponse
    {
        return response()->json(['ok' => true, ...$this->projectState($request, $project)]);
    }

    public function update(MonitoringRequest $request, int $project): JsonResponse
    {
        $this->manager->update($this->ownedProject($request, $project), $request->validated());

        return response()->json(['ok' => true]);
    }

    public function lifecycle(MonitoringRequest $request, int $project): JsonResponse
    {
        $this->manager->lifecycle($this->ownedProject($request, $project), $request->validated('status'));

        return response()->json(['ok' => true]);
    }

    public function destroy(MonitoringRequest $request, int $project): JsonResponse
    {
        $this->manager->remove($this->ownedProject($request, $project));

        return response()->json(['ok' => true]);
    }

    public function addSource(MonitoringRequest $request, int $project): JsonResponse
    {
        $source = $this->manager->addSource($this->ownedProject($request, $project), $request->validated());

        return response()->json(['ok' => true, 'id' => $source->id], 201);
    }

    public function validateSource(Request $request, int $project, int $source): JsonResponse
    {
        $record = $this->ownedProject($request, $project)->sources()->where('status', '!=', 'removed')->findOrFail($source);
        $this->manager->validateSource($record);

        return response()->json(['ok' => true], 202);
    }

    public function removeSource(Request $request, int $project, int $source): JsonResponse
    {
        $record = $this->ownedProject($request, $project)->sources()->findOrFail($source);
        $this->manager->removeSource($record);

        return response()->json(['ok' => true]);
    }

    public function saveSchedule(MonitoringRequest $request, int $project, ?int $schedule = null): JsonResponse
    {
        $record = $this->ownedProject($request, $project);
        $existing = $schedule === null ? null : $record->schedules()->findOrFail($schedule);
        $saved = $this->manager->saveSchedule($record, $request->validated(), $existing);

        return response()->json(['ok' => true, 'id' => $saved->id]);
    }

    public function requestReport(MonitoringRequest $request, int $project): JsonResponse
    {
        $report = $this->manager->requestReport($this->ownedProject($request, $project),
            $request->validated('period'), $request->validated('request_key'));

        return response()->json(['ok' => true, 'id' => $report->id], 202);
    }

    public function regenerate(MonitoringRequest $request, int $report): JsonResponse
    {
        $record = $this->ownedReport($request, $report);
        $next = $this->manager->requestReport($record->project, $record->period,
            $request->validated('request_key'), $record);

        return response()->json(['ok' => true, 'id' => $next->id], 202);
    }

    public function history(Request $request): Response
    {
        $this->requireUser($request);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'],
            'project' => ['nullable', 'integer'], 'status' => ['nullable', 'in:queued,building,completed,partial,empty,failed,cancelled'],
            'period' => ['nullable', 'in:day,three_days,week,month']]);
        $query = MonitoringReport::query()->where('user_id', $request->user()->id)->with('project:id,name');
        foreach (['status', 'period'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['project'])) {
            $this->ownedProject($request, (int) $filters['project']);
            $query->where('project_id', $filters['project']);
        }
        if (! empty($filters['search'])) {
            $query->whereHas('project', fn (Builder $project) => $project->where('name', 'like', '%'.$filters['search'].'%'));
        }
        $reports = $query->latest('id')->paginate(20)->withQueryString();
        $reports->through(fn (MonitoringReport $report) => $this->presentReport($report));

        return Inertia::render('monitoring/History', ['reports' => $reports, 'filters' => $filters,
            'projects' => MonitoringProject::query()->where('user_id', $request->user()->id)->orderBy('name')->get(['id', 'name'])]);
    }

    public function materials(Request $request, int $project): Response
    {
        $record = $this->ownedProject($request, $project);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'in:telegram,youtube,bluesky,mastodon,news']]);
        $query = $record->materials();
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $query) => $query->where('title', 'like', $search)->orWhere('text', 'like', $search));
        }
        if (! empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        return Inertia::render('monitoring/Materials', ['project' => $record->only(['id', 'name', 'timezone']),
            'materials' => $query->latest('published_at')->paginate(30)->withQueryString(), 'filters' => $filters]);
    }

    public function report(Request $request, int $report): Response
    {
        $record = $this->ownedReport($request, $report);
        $items = $record->items()->orderBy('id')->paginate(30)->withQueryString();
        if ($record->expires_at?->isPast()) {
            $items->setCollection(collect());
        }

        return Inertia::render('monitoring/Report', ['report' => $this->presentReport($record), 'items' => $items]);
    }

    public function reportStatus(Request $request, int $report): JsonResponse
    {
        return response()->json(['ok' => true, 'report' => $this->presentReport($this->ownedReport($request, $report))]);
    }

    public function download(Request $request, int $report, string $format, MonitoringExports $exports): HttpResponse
    {
        $record = $this->ownedReport($request, $report);
        abort_if($record->expires_at?->isPast(), 410);
        abort_unless(in_array($record->status, ['completed', 'partial', 'empty'], true), 409);

        return $format === 'xlsx' ? $exports->excel($record) : $exports->json($record);
    }

    private function projectState(Request $request, int $id): array
    {
        $project = $this->ownedProject($request, $id)->load([
            'sources' => fn ($query) => $query->where('status', '!=', 'removed')->orderBy('id'),
            'schedules' => fn ($query) => $query->orderBy('id'),
        ]);
        $reports = $project->reports()->latest('id')->limit(10)->get()->map(fn ($report) => $this->presentReport($report));

        $presented = $project->only(['id', 'name', 'mode', 'filters', 'language', 'timezone', 'status', 'generation',
            'delivery_enabled', 'attach_files', 'empty_delivery', 'collection_enabled', 'collect_interval_minutes']);
        $presented['sources'] = $project->sources->map(fn ($source) => $source->only(['id', 'platform', 'input', 'identity',
            'title', 'status', 'last_collected_at', 'next_collect_at', 'coverage_start', 'coverage_end', 'error']) + ['warnings' => $source->warnings ?? []]);
        $presented['schedules'] = $project->schedules->map(fn ($schedule) => $schedule->only(['id', 'period', 'enabled',
            'time', 'timezone', 'anchor_date', 'next_run_at', 'delivery_enabled']));

        return ['project' => $presented, 'reports' => $reports,
            'limits' => app(MonitoringAccess::class)->limits($request->user()),
            'botLinked' => BotLink::query()->where('user_id', $request->user()->id)->exists()];
    }

    private function presentReport(MonitoringReport $report): array
    {
        return [...$report->only(['id', 'project_id', 'version', 'status', 'period', 'start_at', 'end_at', 'cutoff_at',
            'timezone', 'summary', 'coverage', 'expires_at', 'completed_at', 'created_at', 'delivery_status', 'file_status']),
            'project_name' => $report->configuration['name'] ?? ($report->relationLoaded('project') ? $report->project?->name : null),
            'delivery_status' => $report->delivery_status ?? 'not_requested',
            'expired' => $report->expires_at?->isPast() ?? false];
    }

    private function ownedProject(Request $request, int $id): MonitoringProject
    {
        $this->requireUser($request);

        return MonitoringProject::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function ownedReport(Request $request, int $id): MonitoringReport
    {
        $this->requireUser($request);

        return MonitoringReport::query()->where('user_id', $request->user()->id)->with('project')->findOrFail($id);
    }

    private function requireUser(Request $request): void
    {
        abort_unless($request->user() !== null && ! $request->user()->isBlocked() && $request->user()->hasVerifiedEmail(), 403);
    }
}
