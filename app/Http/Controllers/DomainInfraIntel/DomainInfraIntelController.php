<?php

namespace App\Http\Controllers\DomainInfraIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\DomainInfraIntel\DomainInfraIntelLookupRequest;
use App\Modules\DomainInfraIntel\Application\Services\DomainInfraIntelService;
use Illuminate\Http\JsonResponse;

final class DomainInfraIntelController extends Controller
{
    public function __construct(
        private readonly DomainInfraIntelService $domainInfraIntelService,
    ) {
    }

    public function lookup(DomainInfraIntelLookupRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->domainInfraIntelService->inspect($request->domain()),
        ]);
    }
}

