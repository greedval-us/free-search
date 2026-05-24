<?php

namespace App\Http\Controllers\CompanyIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyIntel\CompanyIntelLookupRequest;
use App\Modules\CompanyIntel\Application\Contracts\CompanyIntelServiceInterface;
use Illuminate\Http\JsonResponse;

class CompanyIntelController extends Controller
{
    public function __construct(
        private readonly CompanyIntelServiceInterface $companyIntelService,
    ) {
    }

    public function lookup(CompanyIntelLookupRequest $request): JsonResponse
    {
        return $this->localizedJsonDataFrom(
            $request->locale(),
            $this->companyIntelService->lookup(
                $request->searchQuery(),
                $request->normalizedDomain(),
            )
        );
    }
}
