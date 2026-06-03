<?php

namespace App\Http\Controllers\Bluesky;

use App\Http\Controllers\Concerns\ResolvesHttpStatusCodeFromException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bluesky\BlueskySearchRequest;
use App\Modules\Bluesky\Search\Contracts\BlueskySearchApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class BlueskySearchController extends Controller
{
    use ResolvesHttpStatusCodeFromException;

    public function __construct(
        private readonly BlueskySearchApplicationServiceInterface $service,
    ) {
    }

    public function search(BlueskySearchRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom($this->service->search($request->toDTO()));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }
}
