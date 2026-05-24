<?php

namespace App\Http\Controllers\EmailIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailIntel\EmailIntelBulkLookupRequest;
use App\Http\Requests\EmailIntel\EmailIntelDomainPostureRequest;
use App\Http\Requests\EmailIntel\EmailIntelLookupRequest;
use App\Modules\EmailIntel\Application\Contracts\DomainMailPostureServiceInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailBulkIntelServiceInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailIntelServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EmailIntelController extends Controller
{
    public function __construct(
        private readonly EmailIntelServiceInterface $emailIntelService,
        private readonly EmailBulkIntelServiceInterface $bulkIntelService,
        private readonly DomainMailPostureServiceInterface $domainMailPostureService,
    ) {
    }

    public function lookup(EmailIntelLookupRequest $request): JsonResponse
    {
        return $this->localizedJsonDataFrom(
            $request->locale(),
            $this->emailIntelService->lookup($request->email())
        );
    }

    public function report(EmailIntelLookupRequest $request): View|Response
    {
        $result = $this->emailIntelService->lookup($request->email());

        return $this->localizedHtmlReportResponse(
            locale: $request->locale(),
            view: 'reports.email-intel.analytics',
            report: $result->toArray(),
            download: $request->boolean('download'),
            filenamePrefix: 'email-intel',
            filenameTarget: str_replace('@', '-at-', $request->email()),
        );
    }

    public function bulk(EmailIntelBulkLookupRequest $request): JsonResponse
    {
        return $this->localizedJsonData(
            $request->locale(),
            $this->bulkIntelService->lookup($request->emails())
        );
    }

    public function domainPosture(EmailIntelDomainPostureRequest $request): JsonResponse
    {
        return $this->localizedJsonData(
            $request->locale(),
            $this->domainMailPostureService->inspect($request->domain())
        );
    }
}
