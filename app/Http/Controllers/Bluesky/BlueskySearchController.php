<?php

namespace App\Http\Controllers\Bluesky;

use App\Http\Controllers\Concerns\ResolvesHttpStatusCodeFromException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bluesky\BlueskyActorResourceRequest;
use App\Http\Requests\Bluesky\BlueskyPostResourceRequest;
use App\Http\Requests\Bluesky\BlueskySearchRequest;
use App\Http\Requests\Bluesky\BlueskyThreadRequest;
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

    public function likes(BlueskyPostResourceRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom(
                $this->service->likes($request->uri(), $request->cid(), $request->limit(), $request->cursor())
            );
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function reposts(BlueskyPostResourceRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom(
                $this->service->reposts($request->uri(), $request->limit(), $request->cursor())
            );
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function thread(BlueskyThreadRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom(
                $this->service->thread($request->uri(), $request->depth(), $request->parentHeight())
            );
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function authorFeed(BlueskyActorResourceRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom(
                $this->service->authorFeed($request->actor(), $request->limit(), $request->cursor(), $request->filter())
            );
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function followers(BlueskyActorResourceRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom(
                $this->service->followers($request->actor(), $request->limit(), $request->cursor())
            );
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function follows(BlueskyActorResourceRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom(
                $this->service->follows($request->actor(), $request->limit(), $request->cursor())
            );
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }
}
