<?php

namespace App\Http\Controllers\CompanyIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyIntel\CompanyIntelLookupRequest;
use App\Modules\CompanyIntel\Application\Services\CompanyIntelService;
use Illuminate\Http\JsonResponse;

class CompanyIntelController extends Controller
{
    public function __construct(
        private readonly CompanyIntelService $companyIntelService,
    ) {
    }

    public function lookup(CompanyIntelLookupRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->companyIntelService->lookup(
                $request->searchQuery(),
                $request->normalizedDomain(),
            ),
        ]);
    }
}
