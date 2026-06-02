<?php

namespace App\Http\Controllers\Mastodon;

use App\Http\Controllers\Concerns\ResolvesHttpStatusCodeFromException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mastodon\MastodonSearchRequest;
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
}
