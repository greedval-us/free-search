<?php

namespace App\Http\Controllers\DocumentIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentIntel\DocumentIntelLookupRequest;
use App\Modules\DocumentIntel\Application\Services\DocumentIntelService;
use Illuminate\Http\JsonResponse;

class DocumentIntelController extends Controller
{
    public function __construct(
        private readonly DocumentIntelService $documentIntelService,
    ) {
    }

    public function lookup(DocumentIntelLookupRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->documentIntelService->lookup(
                $request->searchQuery(),
                $request->normalizedDomain(),
            ),
        ]);
    }
}
