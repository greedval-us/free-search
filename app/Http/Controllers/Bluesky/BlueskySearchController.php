<?php

namespace App\Http\Controllers\Bluesky;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bluesky\BlueskyActorResourceRequest;
use App\Http\Requests\Bluesky\BlueskyPostResourceRequest;
use App\Http\Requests\Bluesky\BlueskySearchRequest;
use App\Http\Requests\Bluesky\BlueskyThreadRequest;
use App\Modules\Bluesky\Search\Contracts\BlueskySearchApplicationServiceInterface;
use Illuminate\Http\JsonResponse;

final class BlueskySearchController extends Controller
{
    public function __construct(
        private readonly BlueskySearchApplicationServiceInterface $service,
    ) {
    }

    public function search(BlueskySearchRequest $request): JsonResponse
    {
        return $this->jsonDataFrom($this->service->search($request->toDTO()));
    }

    public function likes(BlueskyPostResourceRequest $request): JsonResponse
    {
        return $this->jsonDataFrom(
            $this->service->likes($request->uri(), $request->cid(), $request->limit(), $request->cursor())
        );
    }

    public function reposts(BlueskyPostResourceRequest $request): JsonResponse
    {
        return $this->jsonDataFrom(
            $this->service->reposts($request->uri(), $request->limit(), $request->cursor())
        );
    }

    public function thread(BlueskyThreadRequest $request): JsonResponse
    {
        return $this->jsonDataFrom(
            $this->service->thread($request->uri(), $request->depth(), $request->parentHeight())
        );
    }

    public function authorFeed(BlueskyActorResourceRequest $request): JsonResponse
    {
        return $this->jsonDataFrom(
            $this->service->authorFeed($request->actor(), $request->limit(), $request->cursor(), $request->filter())
        );
    }

    public function followers(BlueskyActorResourceRequest $request): JsonResponse
    {
        return $this->jsonDataFrom(
            $this->service->followers($request->actor(), $request->limit(), $request->cursor())
        );
    }

    public function follows(BlueskyActorResourceRequest $request): JsonResponse
    {
        return $this->jsonDataFrom(
            $this->service->follows($request->actor(), $request->limit(), $request->cursor())
        );
    }
}
