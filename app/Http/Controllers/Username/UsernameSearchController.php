<?php

namespace App\Http\Controllers\Username;

use App\Http\Controllers\Controller;
use App\Http\Requests\Username\UsernameSearchRequest;
use App\Modules\Username\Application\Services\UsernameSearchService;
use Illuminate\Http\JsonResponse;

class UsernameSearchController extends Controller
{
    public function __construct(
        private readonly UsernameSearchService $searchService,
    ) {
    }

    public function search(UsernameSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->search($request->toQueryDTO());

        return response()->json([
            'ok' => true,
            ...$result->toArray(),
        ]);
    }
}
