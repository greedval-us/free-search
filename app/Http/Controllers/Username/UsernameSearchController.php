<?php

namespace App\Http\Controllers\Username;

use App\Http\Controllers\Controller;
use App\Http\Requests\Username\UsernameSearchRequest;
use App\Modules\Username\Application\Contracts\UsernameSearchServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class UsernameSearchController extends Controller
{
    public function __construct(
        private readonly UsernameSearchServiceInterface $searchService,
    ) {
    }

    public function search(UsernameSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->search($request->toQueryDTO());

        return $this->jsonOkFrom($result);
    }

    public function report(UsernameSearchRequest $request): View|Response
    {
        $result = $this->searchService->search($request->toQueryDTO());

        return $this->localizedHtmlReportResponse(
            locale: $request->locale(),
            view: 'reports.username.analytics',
            report: $result->toArray(),
            download: $request->boolean('download'),
            filenamePrefix: 'username-analytics',
            filenameTarget: $request->username(),
        );
    }
}
