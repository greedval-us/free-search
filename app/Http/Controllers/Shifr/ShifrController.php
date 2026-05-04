<?php

namespace App\Http\Controllers\Shifr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shifr\ShifrClassicCipherRequest;
use App\Http\Requests\Shifr\ShifrHashRequest;
use App\Http\Requests\Shifr\ShifrIocExtractRequest;
use App\Http\Requests\Shifr\ShifrJwtInspectRequest;
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

    public function inspectJwt(ShifrJwtInspectRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->toolkitService->inspectJwt($request->toDto()),
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
            $request->cipher() === 'rot5' && $request->direction() === 'transform' => $this->shifrService->transformRot5($request->text())->toArray(),
            $request->cipher() === 'vigenere' && $request->direction() === 'encrypt' => $request->key() !== ''
                ? $this->shifrService->encryptVigenere($request->text(), $request->key())->toArray()
                : null,
            $request->cipher() === 'vigenere' && $request->direction() === 'decrypt' => $request->key() !== ''
                ? $this->shifrService->decryptVigenere($request->text(), $request->key())->toArray()
                : null,
            $request->cipher() === 'rail_fence' && $request->direction() === 'encrypt' => $this->shifrService->encryptRailFence($request->text(), $request->rails())->toArray(),
            $request->cipher() === 'rail_fence' && $request->direction() === 'decrypt' => $this->shifrService->decryptRailFence($request->text(), $request->rails())->toArray(),
            $request->cipher() === 'xor' && $request->direction() === 'encrypt' => $request->xorKey() !== ''
                ? $this->shifrService->encryptXor($request->text(), $request->xorKey())->toArray()
                : null,
            $request->cipher() === 'xor' && $request->direction() === 'decrypt' => $request->xorKey() !== ''
                ? $this->shifrService->decryptXor($request->text(), $request->xorKey())->toArray()
                : null,
            $request->cipher() === 'affine' && $request->direction() === 'encrypt' => $this->shifrService->encryptAffine($request->text(), $request->affineA(), $request->affineB())->toArray(),
            $request->cipher() === 'affine' && $request->direction() === 'decrypt' => $this->shifrService->decryptAffine($request->text(), $request->affineA(), $request->affineB())->toArray(),
            $request->cipher() === 'playfair' && $request->direction() === 'encrypt' => $request->playfairKey() !== ''
                ? $this->shifrService->encryptPlayfair($request->text(), $request->playfairKey())->toArray()
                : null,
            $request->cipher() === 'playfair' && $request->direction() === 'decrypt' => $request->playfairKey() !== ''
                ? $this->shifrService->decryptPlayfair($request->text(), $request->playfairKey())->toArray()
                : null,
            $request->cipher() === 'columnar' && $request->direction() === 'encrypt' => $request->columnKey() !== ''
                ? $this->shifrService->encryptColumnar($request->text(), $request->columnKey())->toArray()
                : null,
            $request->cipher() === 'columnar' && $request->direction() === 'decrypt' => $request->columnKey() !== ''
                ? $this->shifrService->decryptColumnar($request->text(), $request->columnKey())->toArray()
                : null,
            $request->cipher() === 'morse' && $request->direction() === 'encrypt' => $this->shifrService->encryptMorse($request->text(), $request->morseSeparator())->toArray(),
            $request->cipher() === 'morse' && $request->direction() === 'decrypt' => $this->shifrService->decryptMorse($request->text(), $request->morseSeparator())->toArray(),
            default => null,
        };

        if ($result === null) {
            return $this->jsonError(__('Unsupported cipher/direction pair or missing required settings.'), 422);
        }

        return $this->jsonOk([
            'data' => $result,
        ]);
    }
}
