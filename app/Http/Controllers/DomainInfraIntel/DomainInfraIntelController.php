<?php

namespace App\Http\Controllers\DomainInfraIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\DomainInfraIntel\DomainInfraIntelLookupRequest;
use App\Modules\DomainInfraIntel\Application\Contracts\DomainInfraIntelServiceInterface;
use Illuminate\Http\JsonResponse;

final class DomainInfraIntelController extends Controller
{
    public function __construct(
        private readonly DomainInfraIntelServiceInterface $domainInfraIntelService,
    ) {
    }

    public function lookup(DomainInfraIntelLookupRequest $request): JsonResponse
    {
        return $this->localizedJsonDataFrom(
            $request->locale(),
            $this->domainInfraIntelService->inspect($request->domain())
        );
    }
}
