<?php

namespace App\Http\Controllers\Shifr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shifr\ShifrClassicCipherRequest;
use App\Http\Requests\Shifr\ShifrHashRequest;
use App\Http\Requests\Shifr\ShifrIocExtractRequest;
use App\Http\Requests\Shifr\ShifrJwtInspectRequest;
use App\Http\Requests\Shifr\ShifrTransformRequest;
use App\Modules\Shifr\Application\Contracts\ShifrClassicCipherServiceInterface;
use App\Modules\Shifr\Application\Contracts\ShifrToolkitServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $this->applyRequestLocaleFor($request);

        $result = $this->classicCipherService->process($request->toDto());

        if ($result === null) {
            return $this->jsonError(__('Unsupported cipher/direction pair or missing required settings.'), 422);
        }

        return $this->jsonOk([
            'data' => $result,
        ]);
    }

    /**
     * @param callable(): array<string, mixed> $resolver
     */
    private function respondWithData(Request $request, callable $resolver): JsonResponse
    {
        $this->applyRequestLocaleFor($request);

        return $this->jsonOk([
            'data' => $resolver(),
        ]);
    }

    private function applyRequestLocaleFor(Request $request): void
    {
        if (method_exists($request, 'locale')) {
            /** @var mixed $locale */
            $locale = $request->locale();
            $this->applyRequestLocale(is_string($locale) ? $locale : app()->getLocale());

            return;
        }

        /** @var mixed $inputLocale */
        $inputLocale = $request->input('locale');
        $this->applyRequestLocale(is_string($inputLocale) ? $inputLocale : app()->getLocale());
    }
}
