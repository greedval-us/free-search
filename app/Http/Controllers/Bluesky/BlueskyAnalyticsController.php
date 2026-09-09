<?php

namespace App\Http\Controllers\Bluesky;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bluesky\BlueskyAnalyticsRequest;
use App\Modules\Bluesky\Analytics\Contracts\BlueskyAnalyticsApplicationServiceInterface;
use App\Support\Reports\ReportSnapshotStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class BlueskyAnalyticsController extends Controller
{
    public function __construct(
        private readonly BlueskyAnalyticsApplicationServiceInterface $service,
        private readonly ReportSnapshotStore $snapshots,
    ) {}

    public function summary(BlueskyAnalyticsRequest $request): JsonResponse
    {
        $query = $request->toDTO();

        return $this->jsonData($this->snapshots->store(
            $request->user()->id, 'bluesky.analytics', get_object_vars($query), $this->service->summary($query)->toArray(),
        ));
    }

    public function report(BlueskyAnalyticsRequest $request): View|Response
    {
        $query = $request->toDTO();
        $report = $this->snapshots->get($request->user()->id, 'bluesky.analytics', get_object_vars($query));
        $target = $query->target !== '' ? $query->target : 'report';

        return $this->localizedHtmlReportResponse(
            locale: $request->locale(),
            view: 'reports.bluesky.analytics',
            report: $report,
            download: $request->boolean('download'),
            filenamePrefix: 'bluesky-analytics',
            filenameTarget: $target,
        );
    }
}
