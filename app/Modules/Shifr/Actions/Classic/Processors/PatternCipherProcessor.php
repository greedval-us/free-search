<?php

namespace App\Modules\Shifr\Actions\Classic\Processors;

use App\Modules\Shifr\Actions\Classic\Contracts\ClassicCipherProcessorInterface;
use App\Modules\Shifr\Actions\Classic\Support\ClassicCipherResultFactory;
use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;
use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherAffinePlayfair;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherRailFence;
use App\Modules\Shifr\Support\ClassicCiphers\ClassicCipherTransposition;

final class PatternCipherProcessor implements ClassicCipherProcessorInterface
{
    public function __construct(
        private readonly ClassicCipherRailFence $railFence,
        private readonly ClassicCipherAffinePlayfair $affinePlayfair,
        private readonly ClassicCipherTransposition $transposition,
        private readonly ClassicCipherResultFactory $resultFactory,
    ) {
    }

    public function supports(string $cipher): bool
    {
        return in_array($cipher, ['rail_fence', 'affine', 'morse'], true);
    }

    public function process(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface
    {
        return match ($dto->cipher) {
            'rail_fence' => $this->resolveRailFence($dto),
            'affine' => $this->resolveAffine($dto),
            'morse' => $this->resolveMorse($dto),
            default => null,
        };
    }

    private function resolveRailFence(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface
    {
        return match ($dto->direction) {
            'encrypt' => $this->resultFactory->fromOriginalAndResult($dto->text, $this->railFence->encrypt($dto->text, $dto->rails)),
            'decrypt' => $this->resultFactory->fromOriginalAndResult($dto->text, $this->railFence->decrypt($dto->text, $dto->rails)),
            default => null,
        };
    }

    private function resolveAffine(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface
    {
        return match ($dto->direction) {
            'encrypt' => $this->resultFactory->fromOriginalAndResult(
                $dto->text,
                $this->affinePlayfair->affineEncrypt($dto->text, $dto->affineA, $dto->affineB)
            ),
            'decrypt' => $this->resultFactory->fromOriginalAndResult(
                $dto->text,
                $this->affinePlayfair->affineDecrypt($dto->text, $dto->affineA, $dto->affineB)
            ),
            default => null,
        };
    }

    private function resolveMorse(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface
    {
        return match ($dto->direction) {
            'encrypt' => $this->resultFactory->fromOriginalAndResult(
                $dto->text,
                $this->transposition->morseEncode($dto->text, $dto->morseSeparator)
            ),
            'decrypt' => $this->resultFactory->fromOriginalAndResult(
                $dto->text,
                $this->transposition->morseDecode($dto->text, $dto->morseSeparator)
            ),
            default => null,
        };
    }
}
