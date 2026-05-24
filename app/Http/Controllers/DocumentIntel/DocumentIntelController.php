<?php

namespace App\Http\Controllers\DocumentIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentIntel\DocumentIntelLookupRequest;
use App\Modules\DocumentIntel\Application\Contracts\DocumentIntelServiceInterface;
use Illuminate\Http\JsonResponse;

class DocumentIntelController extends Controller
{
    public function __construct(
        private readonly DocumentIntelServiceInterface $documentIntelService,
    ) {
    }

    public function lookup(DocumentIntelLookupRequest $request): JsonResponse
    {
        return $this->localizedJsonData(
            $request->locale(),
            $this->documentIntelService->lookup(
                $request->searchQuery(),
                $request->normalizedDomain(),
            )
        );
    }
}
