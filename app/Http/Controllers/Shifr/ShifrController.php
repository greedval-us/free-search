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

        $result = $this->resolveClassicResult($request);

        if ($result === null) {
            return $this->jsonError(__('Unsupported cipher/direction pair or missing required settings.'), 422);
        }

        return $this->jsonOk([
            'data' => $result,
        ]);
    }

    private function resolveClassicResult(ShifrClassicCipherRequest $request): ?array
    {
        $cipher = $request->cipher();
        $direction = $request->direction();

        return match ($cipher) {
            'caesar' => $this->resolveCaesar($request, $direction),
            'atbash' => $this->resolveAtbash($request, $direction),
            'rot13', 'rot47', 'rot5' => $this->resolveRot($request, $cipher, $direction),
            'vigenere' => $this->resolveVigenere($request, $direction),
            'rail_fence' => $this->resolveRailFence($request, $direction),
            'xor' => $this->resolveXor($request, $direction),
            'affine' => $this->resolveAffine($request, $direction),
            'playfair' => $this->resolvePlayfair($request, $direction),
            'columnar' => $this->resolveColumnar($request, $direction),
            'morse' => $this->resolveMorse($request, $direction),
            default => null,
        };
    }

    private function resolveCaesar(ShifrClassicCipherRequest $request, string $direction): ?array
    {
        return match ($direction) {
            'encrypt' => $this->shifrService->encryptCaesar($request->text(), $request->shift())->toArray(),
            'decrypt' => $this->shifrService->decryptCaesar($request->text(), $request->shift())->toArray(),
            default => null,
        };
    }

    private function resolveAtbash(ShifrClassicCipherRequest $request, string $direction): ?array
    {
        return match ($direction) {
            'encrypt' => $this->shifrService->encryptAtbash($request->text())->toArray(),
            'decrypt' => $this->shifrService->decryptAtbash($request->text())->toArray(),
            default => null,
        };
    }

    private function resolveRot(ShifrClassicCipherRequest $request, string $cipher, string $direction): ?array
    {
        if ($direction !== 'transform') {
            return null;
        }

        return match ($cipher) {
            'rot13' => $this->shifrService->transformRot13($request->text())->toArray(),
            'rot47' => $this->shifrService->transformRot47($request->text())->toArray(),
            'rot5' => $this->shifrService->transformRot5($request->text())->toArray(),
            default => null,
        };
    }

    private function resolveVigenere(ShifrClassicCipherRequest $request, string $direction): ?array
    {
        if ($request->key() === '') {
            return null;
        }

        return match ($direction) {
            'encrypt' => $this->shifrService->encryptVigenere($request->text(), $request->key())->toArray(),
            'decrypt' => $this->shifrService->decryptVigenere($request->text(), $request->key())->toArray(),
            default => null,
        };
    }

    private function resolveRailFence(ShifrClassicCipherRequest $request, string $direction): ?array
    {
        return match ($direction) {
            'encrypt' => $this->shifrService->encryptRailFence($request->text(), $request->rails())->toArray(),
            'decrypt' => $this->shifrService->decryptRailFence($request->text(), $request->rails())->toArray(),
            default => null,
        };
    }

    private function resolveXor(ShifrClassicCipherRequest $request, string $direction): ?array
    {
        if ($request->xorKey() === '') {
            return null;
        }

        return match ($direction) {
            'encrypt' => $this->shifrService->encryptXor($request->text(), $request->xorKey())->toArray(),
            'decrypt' => $this->shifrService->decryptXor($request->text(), $request->xorKey())->toArray(),
            default => null,
        };
    }

    private function resolveAffine(ShifrClassicCipherRequest $request, string $direction): ?array
    {
        return match ($direction) {
            'encrypt' => $this->shifrService->encryptAffine($request->text(), $request->affineA(), $request->affineB())->toArray(),
            'decrypt' => $this->shifrService->decryptAffine($request->text(), $request->affineA(), $request->affineB())->toArray(),
            default => null,
        };
    }

    private function resolvePlayfair(ShifrClassicCipherRequest $request, string $direction): ?array
    {
        if ($request->playfairKey() === '') {
            return null;
        }

        return match ($direction) {
            'encrypt' => $this->shifrService->encryptPlayfair($request->text(), $request->playfairKey())->toArray(),
            'decrypt' => $this->shifrService->decryptPlayfair($request->text(), $request->playfairKey())->toArray(),
            default => null,
        };
    }

    private function resolveColumnar(ShifrClassicCipherRequest $request, string $direction): ?array
    {
        if ($request->columnKey() === '') {
            return null;
        }

        return match ($direction) {
            'encrypt' => $this->shifrService->encryptColumnar($request->text(), $request->columnKey())->toArray(),
            'decrypt' => $this->shifrService->decryptColumnar($request->text(), $request->columnKey())->toArray(),
            default => null,
        };
    }

    private function resolveMorse(ShifrClassicCipherRequest $request, string $direction): ?array
    {
        return match ($direction) {
            'encrypt' => $this->shifrService->encryptMorse($request->text(), $request->morseSeparator())->toArray(),
            'decrypt' => $this->shifrService->decryptMorse($request->text(), $request->morseSeparator())->toArray(),
            default => null,
        };
    }
}
