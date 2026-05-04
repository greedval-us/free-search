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

        $result = match (true) {
            $request->cipher() === 'caesar' && $request->direction() === 'encrypt' => $this->shifrService->encryptCaesar($request->text(), $request->shift())->toArray(),
            $request->cipher() === 'caesar' && $request->direction() === 'decrypt' => $this->shifrService->decryptCaesar($request->text(), $request->shift())->toArray(),
            $request->cipher() === 'atbash' && $request->direction() === 'encrypt' => $this->shifrService->encryptAtbash($request->text())->toArray(),
            $request->cipher() === 'atbash' && $request->direction() === 'decrypt' => $this->shifrService->decryptAtbash($request->text())->toArray(),
            $request->cipher() === 'rot13' && $request->direction() === 'transform' => $this->shifrService->transformRot13($request->text())->toArray(),
            $request->cipher() === 'rot47' && $request->direction() === 'transform' => $this->shifrService->transformRot47($request->text())->toArray(),
            default => null,
        };

        if ($result === null) {
            return $this->jsonError(__('Unsupported cipher/direction pair.'), 422);
        }

        return $this->jsonOk([
            'data' => $result,
        ]);
    }
}
