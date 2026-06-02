<?php

namespace App\Modules\Shifr\Actions\Classic\Processors;

use App\Modules\Shifr\Actions\Classic\AtbashDecryptAction;
use App\Modules\Shifr\Actions\Classic\AtbashEncryptAction;
use App\Modules\Shifr\Actions\Classic\CaesarDecryptAction;
use App\Modules\Shifr\Actions\Classic\CaesarEncryptAction;
use App\Modules\Shifr\Actions\Classic\Contracts\ClassicCipherProcessorInterface;
use App\Modules\Shifr\Actions\Classic\Rot13TransformAction;
use App\Modules\Shifr\Actions\Classic\Rot47TransformAction;
use App\Modules\Shifr\Actions\Classic\Support\ClassicCipherResultFactory;
use App\Modules\Shifr\DTO\Classic\AtbashRequestDTO;
use App\Modules\Shifr\DTO\Classic\AtbashResultDTO;
use App\Modules\Shifr\DTO\Classic\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;
use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;
use App\Modules\Shifr\Enums\ShifrCipherDirection;

final class ShiftMirrorCipherProcessor implements ClassicCipherProcessorInterface
{
    public function __construct(
        private readonly CaesarEncryptAction $encryptCaesar,
        private readonly CaesarDecryptAction $decryptCaesar,
        private readonly AtbashEncryptAction $encryptAtbash,
        private readonly AtbashDecryptAction $decryptAtbash,
        private readonly Rot13TransformAction $rot13Transform,
        private readonly Rot47TransformAction $rot47Transform,
        private readonly ClassicCipherResultFactory $resultFactory,
    ) {
    }

    public function supports(string $cipher): bool
    {
        return in_array($cipher, ['caesar', 'atbash', 'rot13', 'rot47', 'rot5'], true);
    }

    public function process(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface
    {
        return match ($dto->cipher) {
            'caesar' => $this->resolveCaesar($dto),
            'atbash' => $this->resolveAtbash($dto),
            'rot13', 'rot47', 'rot5' => $this->resolveRot($dto),
            default => null,
        };
    }

    private function resolveCaesar(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface
    {
        $request = new CaesarCipherRequestDTO([
            'message' => $dto->text,
            'shift' => $dto->shift,
        ]);

        return match ($dto->direction) {
            ShifrCipherDirection::Encrypt->value => $this->encryptCaesar->execute($request),
            ShifrCipherDirection::Decrypt->value => $this->decryptCaesar->execute($request),
            default => null,
        };
    }

    private function resolveAtbash(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface
    {
        $request = new AtbashRequestDTO([
            'message' => $dto->text,
        ]);

        return match ($dto->direction) {
            ShifrCipherDirection::Encrypt->value => $this->encryptAtbash->execute($request),
            ShifrCipherDirection::Decrypt->value => $this->decryptAtbash->execute($request),
            default => null,
        };
    }

    private function resolveRot(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface
    {
        if ($dto->direction !== ShifrCipherDirection::Transform->value) {
            return null;
        }

        $request = new AtbashRequestDTO([
            'message' => $dto->text,
        ]);

        return match ($dto->cipher) {
            'rot13' => $this->rot13Transform->execute($request),
            'rot47' => $this->rot47Transform->execute($request),
            'rot5' => $this->transformRot5($dto->text),
            default => null,
        };
    }

    /**
     * ROT5 is symmetric, so one transform is enough.
     *
     */
    private function transformRot5(string $message): AtbashResultDTO
    {
        $result = preg_replace_callback('/\d/', static function (array $matches): string {
            $digit = (int) $matches[0];

            return (string) (($digit + 5) % 10);
        }, $message);

        return $this->resultFactory->fromOriginalAndResult(
            $message,
            $result ?? $message,
        );
    }
}
