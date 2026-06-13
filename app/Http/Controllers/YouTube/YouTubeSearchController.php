<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeSearchRequest;
use App\Modules\YouTube\Search\Contracts\YouTubeSearchApplicationServiceInterface;
use Illuminate\Http\JsonResponse;

class YouTubeSearchController extends Controller
{
    public function __construct(private readonly YouTubeSearchApplicationServiceInterface $service) {}

    public function videos(YouTubeSearchRequest $request): JsonResponse
    {
        return $this->jsonDataFrom($this->service->searchVideos($request->toDTO()));
    }
}
