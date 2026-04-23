<?php

namespace App\Http\Controllers\Dorks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dorks\DorkSearchRequest;
use App\Modules\Dorks\Application\Services\DorkSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DorkSearchController extends Controller
{
    public function __construct(
        private readonly DorkSearchService $searchService,
    ) {
    }

    public function search(DorkSearchRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        try {
            $data = $this->searchService->search($request->toQueryDTO());
        } catch (Throwable) {
            return $this->jsonError(__('Unable to fetch public matches now. Try again later.'), 503);
        }

        return $this->jsonOk($data);
    }

    public function goals(Request $request): JsonResponse
    {
        $locale = strtolower(trim((string) $request->query('locale', app()->getLocale())));
        if (in_array($locale, ['ru', 'en'], true)) {
            $this->applyRequestLocale($locale);
        }

        return $this->jsonOk([
            'goals' => $this->searchService->availableGoals(),
        ]);
    }

    public function report(DorkSearchRequest $request): View|Response
    {
        $this->applyRequestLocale($request->locale());

        try {
            $data = $this->searchService->search($request->toQueryDTO());
        } catch (Throwable) {
            abort(503, __('Unable to fetch public matches now. Try again later.'));
        }

        return $this->htmlReportResponse(
            view: 'reports.dorks.analytics',
            viewData: $this->reportViewData($data),
            download: $request->boolean('download'),
            filenamePrefix: 'dorks-analytics',
            filenameTarget: $request->target(),
        );
    }
}
