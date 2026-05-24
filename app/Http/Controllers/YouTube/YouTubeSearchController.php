<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Concerns\ResolvesHttpStatusCodeFromException;
use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeSearchRequest;
use App\Modules\YouTube\Search\Contracts\YouTubeSearchApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class YouTubeSearchController extends Controller
{
    use ResolvesHttpStatusCodeFromException;

    public function __construct(private readonly YouTubeSearchApplicationServiceInterface $service) {}

    public function videos(YouTubeSearchRequest $request): JsonResponse
    {
        try {
            return $this->jsonData($this->service->searchVideos($request->toDTO()));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }
}
