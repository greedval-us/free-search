<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Concerns\ResolvesHttpStatusCodeFromException;
use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeAnalyticsRequest;
use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class YouTubeAnalyticsController extends Controller
{
    use ResolvesHttpStatusCodeFromException;

    public function __construct(private readonly YouTubeAnalyticsApplicationServiceInterface $service) {}

    public function summary(YouTubeAnalyticsRequest $request): JsonResponse
    {
        try {
            $query = $request->toDTO();

            return $this->jsonDataFrom($this->service->summary($query));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function report(YouTubeAnalyticsRequest $request): View|Response
    {
        $query = $request->toDTO();
        $report = $this->service->summary($query)->toArray();
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
