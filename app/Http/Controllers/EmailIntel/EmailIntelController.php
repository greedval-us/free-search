<?php

namespace App\Http\Controllers\EmailIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailIntel\EmailIntelLookupRequest;
use App\Modules\EmailIntel\Application\Services\EmailIntelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EmailIntelController extends Controller
{
    public function __construct(
        private readonly EmailIntelService $emailIntelService,
    ) {
    }

    public function lookup(EmailIntelLookupRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->emailIntelService->lookup($request->email())->toArray(),
        ]);
    }

    public function report(EmailIntelLookupRequest $request): View|Response
    {
        $this->applyRequestLocale($request->locale());

        $result = $this->emailIntelService->lookup($request->email());

        return $this->htmlReportResponse(
            view: 'reports.email-intel.analytics',
            viewData: $this->reportViewData($result->toArray()),
            download: $request->boolean('download'),
            filenamePrefix: 'email-intel',
            filenameTarget: str_replace('@', '-at-', $request->email()),
        );
    }
}
