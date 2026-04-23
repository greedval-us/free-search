<?php

namespace App\Http\Controllers\SiteIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteIntel\DomainLiteLookupRequest;
use App\Http\Requests\SiteIntel\SiteIntelAnalyticsRequest;
use App\Http\Requests\SiteIntel\SiteHealthCheckRequest;
use App\Modules\SiteIntel\Application\Services\DomainLiteService;
use App\Modules\SiteIntel\Application\Services\SiteHealthService;
use App\Modules\SiteIntel\Application\Services\SiteIntelAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SiteIntelController extends Controller
{
    public function __construct(
        private readonly SiteHealthService $siteHealthService,
        private readonly DomainLiteService $domainLiteService,
        private readonly SiteIntelAnalyticsService $siteIntelAnalyticsService,
    ) {
    }

    public function siteHealth(SiteHealthCheckRequest $request): JsonResponse
    {
        $url = $request->normalizedUrl();
        if ($url === null) {
            return $this->jsonError(__('Invalid target URL or domain.'), 422);
        }

        $data = $this->siteHealthService->check($url);

        return $this->jsonOk([
            'data' => $data,
        ]);
    }

    public function domainLite(DomainLiteLookupRequest $request): JsonResponse
    {
        $domain = $request->normalizedDomain();
        if ($domain === null) {
            return $this->jsonError(__('Invalid domain.'), 422);
        }

        $data = $this->domainLiteService->lookup($domain);

        return $this->jsonOk([
            'data' => $data,
        ]);
    }

    public function analytics(SiteIntelAnalyticsRequest $request): JsonResponse
    {
        $url = $request->normalizedUrl();
        $domain = $request->normalizedDomain();

        if ($url === null || $domain === null) {
            return $this->jsonError(__('Invalid target URL or domain.'), 422);
        }

        $data = $this->siteIntelAnalyticsService->analyze($url, $domain);

        return $this->jsonOk([
            'data' => $data,
        ]);
    }

    public function report(SiteIntelAnalyticsRequest $request): View|Response
    {
        $this->applyRequestLocale($request->locale());

        $url = $request->normalizedUrl();
        $domain = $request->normalizedDomain();

        if ($url === null || $domain === null) {
            abort(422, __('Invalid target URL or domain.'));
        }

        return $this->htmlReportResponse(
            view: 'reports.site-intel.analytics',
            viewData: $this->reportViewData($this->siteIntelAnalyticsService->analyze($url, $domain)),
            download: $request->boolean('download'),
            filenamePrefix: 'site-intel-analytics',
            filenameTarget: $domain,
        );
    }
}
