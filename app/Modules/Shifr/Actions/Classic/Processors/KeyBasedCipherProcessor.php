<?php

namespace App\Modules\Shifr\Actions\Classic\Processors;

use App\Modules\Shifr\Actions\Classic\Contracts\ClassicCipherProcessorInterface;
use App\Modules\Shifr\Actions\Classic\Support\ClassicCipherResultFactory;
use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherAffinePlayfair;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherTransposition;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherVigenere;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherXor;

final class KeyBasedCipherProcessor implements ClassicCipherProcessorInterface
{
    public function __construct(
        private readonly ClassicCipherVigenere $vigenere,
        private readonly ClassicCipherXor $xor,
        private readonly ClassicCipherAffinePlayfair $affinePlayfair,
        private readonly ClassicCipherTransposition $transposition,
        private readonly ClassicCipherResultFactory $resultFactory,
    ) {
    }

    public function supports(string $cipher): bool
    {
        return in_array($cipher, ['vigenere', 'xor', 'playfair', 'columnar'], true);
    }

    public function process(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->cipher) {
            'vigenere' => $this->resolveVigenere($dto),
            'xor' => $this->resolveXor($dto),
            'playfair' => $this->resolvePlayfair($dto),
            'columnar' => $this->resolveColumnar($dto),
            default => null,
        };
    }

    private function resolveVigenere(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->key === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->resultFactory->fromOriginalAndResult($dto->text, $this->vigenere->encrypt($dto->text, $dto->key)),
            'decrypt' => $this->resultFactory->fromOriginalAndResult($dto->text, $this->vigenere->decrypt($dto->text, $dto->key)),
            default => null,
        };
    }

    private function resolveXor(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->xorKey === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->resultFactory->fromOriginalAndResult($dto->text, $this->xor->encrypt($dto->text, $dto->xorKey)),
            'decrypt' => $this->resultFactory->fromOriginalAndResult($dto->text, $this->xor->decrypt($dto->text, $dto->xorKey)),
            default => null,
        };
    }

    private function resolvePlayfair(ClassicCipherLookupDTO $dto): ?array
    {
        if ($dto->playfairKey === '') {
            return null;
        }

        return match ($dto->direction) {
            'encrypt' => $this->resultFactory->fromOriginalAndResult(
                $dto->text,
                $this->affinePlayfair->playfairEncrypt($dto->text, $dto->playfairKey)
            ),
            'decrypt' => $this->resultFactory->fromOriginalAndResult(
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
            'encrypt' => $this->resultFactory->fromOriginalAndResult(
                $dto->text,
                $this->transposition->columnarEncrypt($dto->text, $dto->columnKey)
            ),
            'decrypt' => $this->resultFactory->fromOriginalAndResult(
                $dto->text,
                $this->transposition->columnarDecrypt($dto->text, $dto->columnKey)
            ),
            default => null,
        };
    }
}
