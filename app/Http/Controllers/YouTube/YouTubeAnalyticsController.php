<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeAnalyticsRequest;
use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use App\Support\Reports\ReportSnapshotStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class YouTubeAnalyticsController extends Controller
{
    public function __construct(
        private readonly YouTubeAnalyticsApplicationServiceInterface $service,
        private readonly ReportSnapshotStore $snapshots,
    ) {}

    public function summary(YouTubeAnalyticsRequest $request): JsonResponse
    {
        $query = $request->toDTO();

        return $this->jsonData($this->snapshots->store(
            $request->user()->id, 'youtube.analytics', get_object_vars($query), $this->service->summary($query)->toArray(),
        ));
    }

    public function report(YouTubeAnalyticsRequest $request): View|Response
    {
        $query = $request->toDTO();
        $report = $this->snapshots->get($request->user()->id, 'youtube.analytics', get_object_vars($query));
        $target = $query->mode === 'video'
            ? $query->videoId
            : $query->channelId;

        return $this->localizedHtmlReportResponse(
            locale: $request->locale(),
            view: 'reports.youtube.analytics',
            report: $report,
            download: $request->boolean('download'),
            filenamePrefix: 'youtube-analytics',
            filenameTarget: $target !== '' ? $target : 'report',
        );
    }
}
