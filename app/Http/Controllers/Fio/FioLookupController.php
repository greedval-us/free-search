<?php

namespace App\Http\Controllers\Fio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fio\FioLookupRequest;
use App\Modules\Fio\Application\Services\FioPublicSearchService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class FioLookupController extends Controller
{
    public function __construct(
        private readonly FioPublicSearchService $fioPublicSearchService,
    ) {
    }

    public function lookup(FioLookupRequest $request): JsonResponse
    {
        app()->setLocale($request->locale());

        try {
            $result = $this->fioPublicSearchService->search($request->fullName());
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'data' => $result->toArray(),
        ]);
    }
}
