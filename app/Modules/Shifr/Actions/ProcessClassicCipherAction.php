<?php

namespace App\Modules\Shifr\Actions;

use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;
use App\Modules\Shifr\DTO\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\ClassicCipherLookupDTO;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherAffinePlayfair;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherRailFence;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherTransposition;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherVigenere;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherXor;

final class ProcessClassicCipherAction
{
    public function __construct(
        private readonly CaesarEncryptAction $encryptCaesar,
        private readonly CaesarDecryptAction $decryptCaesar,
        private readonly AtbashEncryptAction $encryptAtbash,
        private readonly AtbashDecryptAction $decryptAtbash,
        private readonly Rot13TransformAction $rot13Transform,
        private readonly Rot47TransformAction $rot47Transform,
        private readonly ClassicCipherTransposition $transposition,
        private readonly ClassicCipherVigenere $vigenere,
        private readonly ClassicCipherRailFence $railFence,
        private readonly ClassicCipherXor $xor,
        private readonly ClassicCipherAffinePlayfair $affinePlayfair,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function execute(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->cipher) {
            'caesar' => $this->resolveCaesar($dto),
            'atbash' => $this->resolveAtbash($dto),
            'rot13', 'rot47', 'rot5' => $this->resolveRot($dto),
            'vigenere' => $this->resolveVigenere($dto),
            'rail_fence' => $this->resolveRailFence($dto),
            'xor' => $this->resolveXor($dto),
            'affine' => $this->resolveAffine($dto),
            'playfair' => $this->resolvePlayfair($dto),
            'columnar' => $this->resolveColumnar($dto),
            'morse' => $this->resolveMorse($dto),
            default => null,
        };
    }

    private function resolveCaesar(ClassicCipherLookupDTO $dto): ?array
    {
        $request = new CaesarCipherRequestDTO([
            'message' => $dto->text,
            'shift' => $dto->shift,
        ]);

        return match ($dto->direction) {
            'encrypt' => $this->encryptCaesar->execute($request)->toArray(),
            'decrypt' => $this->decryptCaesar->execute($request)->toArray(),
            default => null,
        };
    }

    private function resolveAtbash(ClassicCipherLookupDTO $dto): ?array
    {
        $request = new AtbashRequestDTO([
            'message' => $dto->text,
        ]);

        return match ($dto->direction) {
            'encrypt' => $this->encryptAtbash->execute($request)->toArray(),
            'decrypt' => $this->decryptAtbash->execute($request)->toArray(),
            default => null,
        };
    }

    private function resolveRot(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->direction !== 'transform') {
            return null;
        }

        $request = new AtbashRequestDTO([
            'message' => $dto->text,
        ]);

        return match ($dto->cipher) {
            'rot13' => $this->rot13Transform->execute($request)->toArray(),
            'rot47' => $this->rot47Transform->execute($request)->toArray(),
            'rot5' => $this->transformRot5($dto->text)->toArray(),
            default => null,
        };
    }

    private function resolveVigenere(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->key === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->buildResult($dto->text, $this->vigenere->encrypt($dto->text, $dto->key)),
            'decrypt' => $this->buildResult($dto->text, $this->vigenere->decrypt($dto->text, $dto->key)),
            default => null,
        };
    }

    private function resolveRailFence(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->direction) {
            'encrypt' => $this->buildResult($dto->text, $this->railFence->encrypt($dto->text, $dto->rails)),
            'decrypt' => $this->buildResult($dto->text, $this->railFence->decrypt($dto->text, $dto->rails)),
            default => null,
        };
    }

    private function resolveXor(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->xorKey === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->buildResult($dto->text, $this->xor->encrypt($dto->text, $dto->xorKey)),
            'decrypt' => $this->buildResult($dto->text, $this->xor->decrypt($dto->text, $dto->xorKey)),
            default => null,
        };
    }

    private function resolveAffine(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->direction) {
            'encrypt' => $this->buildResult(
                $dto->text,
                $this->affinePlayfair->affineEncrypt($dto->text, $dto->affineA, $dto->affineB)
            ),
            'decrypt' => $this->buildResult(
                $dto->text,
                $this->affinePlayfair->affineDecrypt($dto->text, $dto->affineA, $dto->affineB)
            ),
            default => null,
        };
    }

    private function resolvePlayfair(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->playfairKey === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->buildResult(
                $dto->text,
                $this->affinePlayfair->playfairEncrypt($dto->text, $dto->playfairKey)
            ),
            'decrypt' => $this->buildResult(
                $dto->text,
                $this->affinePlayfair->playfairDecrypt($dto->text, $dto->playfairKey)
            ),
            default => null,
        };
    }

    private function resolveColumnar(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->columnKey === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->buildResult(
                $dto->text,
                $this->transposition->columnarEncrypt($dto->text, $dto->columnKey)
            ),
            'decrypt' => $this->buildResult(
                $dto->text,
                $this->transposition->columnarDecrypt($dto->text, $dto->columnKey)
            ),
            default => null,
        };
    }

    private function resolveMorse(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->direction) {
            'encrypt' => $this->buildResult(
                $dto->text,
                $this->transposition->morseEncode($dto->text, $dto->morseSeparator)
            ),
            'decrypt' => $this->buildResult(
                $dto->text,
                $this->transposition->morseDecode($dto->text, $dto->morseSeparator)
            ),
            default => null,
        };
    }

    private function transformRot5(string $message): AtbashResultDTO
    {
        $result = preg_replace_callback('/\d/', static function (array $matches): string {
            $digit = (int) $matches[0];

            return (string) (($digit + 5) % 10);
        }, $message);

        return new AtbashResultDTO(
            original: $message,
            result: $result ?? $message,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResult(string $original, string $result): array
    {
        return (new AtbashResultDTO(
            original: $original,
            result: $result,
        ))->toArray();
    }
}
