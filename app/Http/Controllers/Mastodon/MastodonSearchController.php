<?php

namespace App\Http\Controllers\Mastodon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mastodon\MastodonAccountResourceRequest;
use App\Http\Requests\Mastodon\MastodonSearchRequest;
use App\Http\Requests\Mastodon\MastodonStatusContextRequest;
use App\Http\Requests\Mastodon\MastodonTagTimelineRequest;
use App\Modules\Mastodon\Search\Contracts\MastodonSearchApplicationServiceInterface;
use Illuminate\Http\JsonResponse;

final class MastodonSearchController extends Controller
{
    public function __construct(
        private readonly MastodonSearchApplicationServiceInterface $service,
    ) {
    }

    public function search(MastodonSearchRequest $request): JsonResponse
    {
        return $this->jsonDataFrom($this->service->search($request->toDTO()));
    }

    public function context(MastodonStatusContextRequest $request): JsonResponse
    {
        return $this->jsonDataFrom($this->service->context($request->statusId()));
    }

    public function accountStatuses(MastodonAccountResourceRequest $request): JsonResponse
    {
        return $this->jsonDataFrom($this->service->accountStatuses($request->accountId(), $request->limit(), $request->maxId()));
    }

    public function accountFollowers(MastodonAccountResourceRequest $request): JsonResponse
    {
        return $this->jsonDataFrom($this->service->accountFollowers($request->accountId(), $request->limit(), $request->maxId()));
    }

    public function tagTimeline(MastodonTagTimelineRequest $request): JsonResponse
    {
        return $this->jsonDataFrom($this->service->tagTimeline($request->tagName(), $request->limit(), $request->maxId()));
    }
}
