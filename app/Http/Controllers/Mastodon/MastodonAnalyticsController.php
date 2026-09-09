<?php

namespace App\Http\Controllers\Mastodon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mastodon\MastodonAnalyticsRequest;
use App\Modules\Mastodon\Analytics\Contracts\MastodonAnalyticsApplicationServiceInterface;
use App\Support\Reports\ReportSnapshotStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class MastodonAnalyticsController extends Controller
{
    public function __construct(
        private readonly MastodonAnalyticsApplicationServiceInterface $service,
        private readonly ReportSnapshotStore $snapshots,
    ) {}

    public function summary(MastodonAnalyticsRequest $request): JsonResponse
    {
        $query = $request->toDTO();

        return $this->jsonData($this->snapshots->store(
            $request->user()->id, 'mastodon.analytics', get_object_vars($query), $this->service->summary($query)->toArray(),
        ));
    }

    public function report(MastodonAnalyticsRequest $request): View|Response
    {
        $query = $request->toDTO();
        $report = $this->snapshots->get($request->user()->id, 'mastodon.analytics', get_object_vars($query));
        $target = $query->target !== '' ? $query->target : 'report';

        return $this->localizedHtmlReportResponse(
            locale: $request->locale(),
            view: 'reports.mastodon.analytics',
            report: $report,
            download: $request->boolean('download'),
            filenamePrefix: 'mastodon-analytics',
            filenameTarget: $target,
        );
    }
}
