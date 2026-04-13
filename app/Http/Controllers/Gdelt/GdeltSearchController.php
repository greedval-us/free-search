<?php

namespace App\Http\Controllers\Gdelt;

use App\Http\Requests\Gdelt\GdeltSearchRequest;
use App\Modules\Gdelt\Search\Contracts\GdeltSearchApplicationServiceInterface;
use Illuminate\Http\JsonResponse;

class GdeltSearchController extends BaseGdeltController
{
    public function __construct(
        private readonly GdeltSearchApplicationServiceInterface $searchApplicationService,
    ) {
    }

    public function articles(GdeltSearchRequest $request): JsonResponse
    {
        $result = $this->searchApplicationService->search($request->toQueryDTO());

        return $this->jsonPayload($result->toArray());
    }
}
