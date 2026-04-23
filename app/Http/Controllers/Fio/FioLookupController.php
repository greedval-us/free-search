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
        $this->applyRequestLocale($request->locale());

        try {
            $result = $this->fioPublicSearchService->search(
                $request->fullName(),
                $request->qualifier(),
            );
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), 502);
        }

        return $this->jsonOk([
            'data' => $result->toArray(),
        ]);
    }
}
