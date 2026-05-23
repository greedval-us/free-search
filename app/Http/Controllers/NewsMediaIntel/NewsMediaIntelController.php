<?php

namespace App\Http\Controllers\NewsMediaIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsMediaIntel\NewsMediaIntelLookupRequest;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsMediaIntelServiceInterface;
use Illuminate\Http\JsonResponse;

final class NewsMediaIntelController extends Controller
{
    public function __construct(
        private readonly NewsMediaIntelServiceInterface $newsMediaIntelService,
    ) {
    }

    public function lookup(NewsMediaIntelLookupRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->newsMediaIntelService->monitor($request->searchQuery())->toArray(),
        ]);
    }
}
