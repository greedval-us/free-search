<?php

namespace App\Http\Controllers\SiteIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteIntel\DomainLiteLookupRequest;
use App\Http\Requests\SiteIntel\SeoAuditRequest;
use App\Http\Requests\SiteIntel\SiteHealthCheckRequest;
use App\Http\Requests\SiteIntel\SiteIntelAnalyticsRequest;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelAnalyticsServiceInterface;
use App\Support\Reports\ReportSnapshotStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SiteIntelController extends Controller
{
    public function __construct(
        private readonly SiteHealthServiceInterface $siteHealthService,
        private readonly DomainLiteServiceInterface $domainLiteService,
        private readonly SiteIntelAnalyticsServiceInterface $siteIntelAnalyticsService,
        private readonly SeoAuditServiceInterface $seoAuditService,
        private readonly ReportSnapshotStore $snapshots,
    ) {}

    public function siteHealth(SiteHealthCheckRequest $request): JsonResponse
    {
        $url = $request->normalizedUrl();
        if ($url === null) {
            return $this->jsonError(__('errors.api.site_intel.invalid_target'), 422);
        }

        return $this->jsonDataFrom($this->siteHealthService->check($url));
    }

    public function domainLite(DomainLiteLookupRequest $request): JsonResponse
    {
        $domain = $request->normalizedDomain();
        if ($domain === null) {
            return $this->jsonError(__('errors.api.site_intel.invalid_domain'), 422);
        }

        return $this->jsonDataFrom($this->domainLiteService->lookup($domain));
    }

    public function analytics(SiteIntelAnalyticsRequest $request): JsonResponse
    {
        $url = $request->normalizedUrl();
        $domain = $request->normalizedDomain();

        if ($url === null || $domain === null) {
            return $this->jsonError(__('errors.api.site_intel.invalid_target'), 422);
        }

        return $this->jsonData($this->snapshots->store(
            $request->user()->id,
            'site-intel.analytics',
            ['url' => $url, 'domain' => $domain],
            $this->siteIntelAnalyticsService->analyze($url, $domain)->toArray(),
        ));
    }

    public function seoAudit(SeoAuditRequest $request): JsonResponse
    {
        $url = $request->normalizedUrl();
        if ($url === null) {
            return $this->jsonError(__('errors.api.site_intel.invalid_target'), 422);
        }

        return $this->jsonData($this->snapshots->store(
            $request->user()->id,
            'site-intel.seo-audit',
            $this->seoReportParameters($request),
            $this->seoAuditService->audit($url, $request->crawlLimit(), $request->platformType())->toArray(),
        ));
    }

    public function seoReport(SeoAuditRequest $request): View|Response
    {
        $url = $request->normalizedUrl();
        if ($url === null) {
            abort(422, __('errors.api.site_intel.invalid_target'));
        }

        return $this->localizedHtmlReportResponse(
            locale: $request->locale(),
            view: 'reports.site-intel.seo-audit',
            report: $this->snapshots->get($request->user()->id, 'site-intel.seo-audit', $this->seoReportParameters($request)),
            download: $request->boolean('download'),
            filenamePrefix: 'site-intel-seo-audit',
            filenameTarget: (string) parse_url($url, PHP_URL_HOST),
        );
    }

    public function report(SiteIntelAnalyticsRequest $request): View|Response
    {
        $url = $request->normalizedUrl();
        $domain = $request->normalizedDomain();

        if ($url === null || $domain === null) {
            abort(422, __('errors.api.site_intel.invalid_target'));
        }

        return $this->localizedHtmlReportResponse(
            locale: $request->locale(),
            view: 'reports.site-intel.analytics',
            report: $this->snapshots->get($request->user()->id, 'site-intel.analytics', ['url' => $url, 'domain' => $domain]),
            download: $request->boolean('download'),
            filenamePrefix: 'site-intel-analytics',
            filenameTarget: $domain,
        );
    }

    /** @return array<string, mixed> */
    private function seoReportParameters(SeoAuditRequest $request): array
    {
        return ['url' => $request->normalizedUrl(), 'crawlLimit' => $request->crawlLimit(), 'platformType' => $request->platformType()];
    }
}
