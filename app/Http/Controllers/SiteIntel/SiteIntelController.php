<?php

namespace App\Http\Controllers\SiteIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteIntel\DomainLiteLookupRequest;
use App\Http\Requests\SiteIntel\SiteHealthCheckRequest;
use App\Modules\SiteIntel\Application\Services\DomainLiteService;
use App\Modules\SiteIntel\Application\Services\SiteHealthService;
use Illuminate\Http\JsonResponse;

class SiteIntelController extends Controller
{
    public function __construct(
        private readonly SiteHealthService $siteHealthService,
        private readonly DomainLiteService $domainLiteService,
    ) {
    }

    public function siteHealth(SiteHealthCheckRequest $request): JsonResponse
    {
        $url = $request->normalizedUrl();
        if ($url === null) {
            return response()->json([
                'ok' => false,
                'message' => __('Invalid target URL or domain.'),
            ], 422);
        }

        $data = $this->siteHealthService->check($url);

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    public function domainLite(DomainLiteLookupRequest $request): JsonResponse
    {
        $domain = $request->normalizedDomain();
        if ($domain === null) {
            return response()->json([
                'ok' => false,
                'message' => __('Invalid domain.'),
            ], 422);
        }

        $data = $this->domainLiteService->lookup($domain);

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }
}

