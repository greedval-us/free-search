<?php

namespace App\Http\Controllers\Username;

use App\Http\Controllers\Controller;
use App\Http\Requests\Username\UsernameSearchRequest;
use App\Modules\Username\Application\Services\UsernameSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class UsernameSearchController extends Controller
{
    public function __construct(
        private readonly UsernameSearchService $searchService,
    ) {
    }

    public function search(UsernameSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->search($request->toQueryDTO());

        return $this->jsonOk($result->toArray());
    }

    public function report(UsernameSearchRequest $request): View|Response
    {
        $this->applyRequestLocale($request->locale());

        $result = $this->searchService->search($request->toQueryDTO());

        return $this->htmlReportResponse(
            view: 'reports.username.analytics',
            viewData: $this->reportViewData($result->toArray()),
            download: $request->boolean('download'),
            filenamePrefix: 'username-analytics',
            filenameTarget: $request->username(),
        );
    }
}
