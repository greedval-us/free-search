<?php

namespace App\Http\Controllers\Mastodon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mastodon\MastodonAnalyticsRequest;
use App\Modules\Mastodon\Analytics\Contracts\MastodonAnalyticsApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class MastodonAnalyticsController extends Controller
{
    public function __construct(
        private readonly MastodonAnalyticsApplicationServiceInterface $service,
    ) {
    }

    public function summary(MastodonAnalyticsRequest $request): JsonResponse
    {
        return $this->jsonDataFrom($this->service->summary($request->toDTO()));
    }

    public function report(MastodonAnalyticsRequest $request): View|Response
    {
        $query = $request->toDTO();
        $report = $this->service->summary($query)->toArray();
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
