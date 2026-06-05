<?php

namespace App\Http\Controllers\Bluesky;

use App\Http\Controllers\Concerns\ResolvesHttpStatusCodeFromException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bluesky\BlueskyAnalyticsRequest;
use App\Modules\Bluesky\Analytics\Contracts\BlueskyAnalyticsApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

final class BlueskyAnalyticsController extends Controller
{
    use ResolvesHttpStatusCodeFromException;

    public function __construct(
        private readonly BlueskyAnalyticsApplicationServiceInterface $service,
    ) {
    }

    public function summary(BlueskyAnalyticsRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom($this->service->summary($request->toDTO()));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function report(BlueskyAnalyticsRequest $request): View|Response
    {
        $query = $request->toDTO();
        $report = $this->service->summary($query)->toArray();
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
