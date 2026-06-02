<?php

namespace App\Http\Controllers\Mastodon;

use App\Http\Controllers\Concerns\ResolvesHttpStatusCodeFromException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mastodon\MastodonAnalyticsRequest;
use App\Modules\Mastodon\Analytics\Contracts\MastodonAnalyticsApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class MastodonAnalyticsController extends Controller
{
    use ResolvesHttpStatusCodeFromException;

    public function __construct(
        private readonly MastodonAnalyticsApplicationServiceInterface $service,
    ) {
    }

    public function summary(MastodonAnalyticsRequest $request): JsonResponse
    {
        try {
            return $this->jsonDataFrom($this->service->summary($request->toDTO()));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }
}
