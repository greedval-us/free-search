<?php

namespace App\Http\Controllers\NewsMediaIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsMediaIntel\NewsMarketingAnalyticsRequest;
use App\Modules\NewsMediaIntel\Application\Services\Marketing\NewsMarketingAnalyticsService;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Support\Http\DocumentResponseHeaders;
use App\Support\Reports\ReportSnapshotStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class NewsMarketingAnalyticsController extends Controller
{
    public function __construct(private readonly ReportSnapshotStore $snapshots) {}

    public function options(NewsMediaIntelConfig $config): JsonResponse
    {
        return $this->jsonData([
            'categories' => ['news', 'general'],
            'languages' => config('osint.news_media_intel.searxng.languages'),
            'engines' => config('osint.news_media_intel.searxng.available_engines'),
            'maxPages' => $config->searxngMaxPages(),
            'defaults' => ['language' => $config->searxngLanguage(), 'timeRange' => $config->searxngTimeRange(),
                'safeSearch' => $config->searxngSafeSearch(), 'maxPages' => $config->searxngMaxPages()],
        ]);
    }

    public function analytics(NewsMarketingAnalyticsRequest $request, NewsMarketingAnalyticsService $analytics): JsonResponse
    {
        $this->applyRequestLocale($request->locale());
        $result = $analytics->analyze($request->toLookupDTO());
        $id = (string) Str::uuid();
        $result['reportId'] = $id;
        $result['reportExpiresAt'] = now()->addSeconds(max(1, (int) config('access.report_snapshot_ttl_seconds', 3600)))->toIso8601String();
        $this->snapshots->store($request->user()->id, 'news-media-intel.analytics', ['id' => $id], $result);

        return $this->jsonData($result);
    }

    public function report(Request $request, string $reportId): View|Response|JsonResponse
    {
        $data = $request->validate(['format' => ['sometimes', Rule::in(['html', 'json'])],
            'download' => ['sometimes', 'boolean'], 'locale' => ['sometimes', Rule::in(['ru', 'en'])]]);
        $report = $this->snapshots->get($request->user()->id, 'news-media-intel.analytics', ['id' => $reportId]);
        if (($data['format'] ?? 'html') === 'json') {
            $response = response()->json($report, 200, DocumentResponseHeaders::download(), JSON_UNESCAPED_UNICODE);

            return $response->header('Content-Disposition', $response->headers->makeDisposition('attachment', 'news-media-analytics-'.$reportId.'.json'));
        }

        return $this->localizedHtmlReportResponse(locale: $data['locale'] ?? app()->getLocale(),
            view: 'reports.news-media-intel.analytics', report: $report, download: (bool) ($data['download'] ?? false),
            filenamePrefix: 'news-media-analytics', filenameTarget: $report['query']);
    }
}
