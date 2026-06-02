<?php

namespace App\Http\Controllers\Mastodon;

use App\Http\Controllers\Concerns\ResolvesHttpStatusCodeFromException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mastodon\MastodonAccountResourceRequest;
use App\Http\Requests\Mastodon\MastodonSearchRequest;
use App\Http\Requests\Mastodon\MastodonStatusContextRequest;
use App\Modules\Mastodon\Search\Contracts\MastodonSearchApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class MastodonSearchController extends Controller
{
    use ResolvesHttpStatusCodeFromException;

    public function __construct(
        private readonly MastodonSearchApplicationServiceInterface $service,
    ) {
    }

    public function search(MastodonSearchRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom($this->service->search($request->toDTO()));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function context(MastodonStatusContextRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom($this->service->context($request->statusId()));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function accountStatuses(MastodonAccountResourceRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom($this->service->accountStatuses($request->accountId(), $request->limit()));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function accountFollowers(MastodonAccountResourceRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom($this->service->accountFollowers($request->accountId(), $request->limit()));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }
}
