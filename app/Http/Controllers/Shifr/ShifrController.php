<?php

namespace App\Http\Controllers\Shifr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shifr\ShifrClassicCipherRequest;
use App\Http\Requests\Shifr\ShifrHashRequest;
use App\Http\Requests\Shifr\ShifrIocExtractRequest;
use App\Http\Requests\Shifr\ShifrTransformRequest;
use App\Modules\Shifr\Application\Contracts\ShifrToolkitServiceInterface;
use App\Modules\Shifr\Contracts\ShifrServiceInterface;
use Illuminate\Http\JsonResponse;

final class ShifrController extends Controller
{
    public function __construct(
        private readonly ShifrToolkitServiceInterface $toolkitService,
        private readonly ShifrServiceInterface $shifrService,
    ) {
    }

    public function hash(ShifrHashRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->toolkitService->hash($request->toDto()),
        ]);
    }

    public function transform(ShifrTransformRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->toolkitService->transform($request->toDto()),
        ]);
    }

    public function extractIocs(ShifrIocExtractRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->toolkitService->extractIocs($request->toDto()),
        ]);
    }

    public function classic(ShifrClassicCipherRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        $result = match ([$request->cipher(), $request->direction()]) {
            ['caesar', 'encrypt'] => $this->shifrService->encryptCaesar($request->text(), $request->shift())->toArray(),
            ['caesar', 'decrypt'] => $this->shifrService->decryptCaesar($request->text(), $request->shift())->toArray(),
            ['atbash', 'encrypt'] => $this->shifrService->encryptAtbash($request->text())->toArray(),
            ['atbash', 'decrypt'] => $this->shifrService->decryptAtbash($request->text())->toArray(),
        };

        return $this->jsonOk([
            'data' => $result,
        ]);
    }
}
