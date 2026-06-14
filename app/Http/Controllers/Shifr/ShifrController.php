<?php

namespace App\Http\Controllers\Shifr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shifr\AbstractShifrRequest;
use App\Http\Requests\Shifr\ShifrClassicCipherRequest;
use App\Http\Requests\Shifr\ShifrHashRequest;
use App\Http\Requests\Shifr\ShifrIocExtractRequest;
use App\Http\Requests\Shifr\ShifrJwtInspectRequest;
use App\Http\Requests\Shifr\ShifrTransformRequest;
use App\Modules\Shifr\Application\Contracts\ShifrClassicCipherServiceInterface;
use App\Modules\Shifr\Application\Contracts\ShifrToolkitServiceInterface;
use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;
use Illuminate\Http\JsonResponse;

final class ShifrController extends Controller
{
    public function __construct(
        private readonly ShifrToolkitServiceInterface $toolkitService,
        private readonly ShifrClassicCipherServiceInterface $classicCipherService,
    ) {
    }

    public function hash(ShifrHashRequest $request): JsonResponse
    {
        return $this->respondWithData(
            $request,
            fn () => $this->toolkitService->hash($request->toDto()),
        );
    }

    public function transform(ShifrTransformRequest $request): JsonResponse
    {
        return $this->respondWithData(
            $request,
            fn () => $this->toolkitService->transform($request->toDto()),
        );
    }

    public function extractIocs(ShifrIocExtractRequest $request): JsonResponse
    {
        return $this->respondWithData(
            $request,
            fn () => $this->toolkitService->extractIocs($request->toDto()),
        );
    }

    public function inspectJwt(ShifrJwtInspectRequest $request): JsonResponse
    {
        return $this->respondWithData(
            $request,
            fn () => $this->toolkitService->inspectJwt($request->toDto()),
        );
    }

    public function classic(ShifrClassicCipherRequest $request): JsonResponse
    {
        return $this->respondWithOptionalData(
            $request,
            fn () => $this->classicCipherService->process($request->toDto()),
            __('errors.api.shifr.unsupported_cipher_configuration')
        );
    }

    /**
     * @param callable(): ShifrResultDataInterface $resolver
     */
    private function respondWithData(AbstractShifrRequest $request, callable $resolver): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonDataFrom($resolver());
    }

    /**
     * @param callable(): ShifrResultDataInterface|null $resolver
     */
    private function respondWithOptionalData(
        AbstractShifrRequest $request,
        callable $resolver,
        string $notSupportedMessage,
    ): JsonResponse {
        $this->applyRequestLocale($request->locale());

        $result = $resolver();

        if ($result === null) {
            return $this->jsonError($notSupportedMessage, 422);
        }

        return $this->jsonDataFrom($result);
    }
}
