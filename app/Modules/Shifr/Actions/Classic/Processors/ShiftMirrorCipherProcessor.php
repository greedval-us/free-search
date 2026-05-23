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
use App\Modules\Shifr\DTO\Classic\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;

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

    public function process(ClassicCipherLookupDTO $dto): ?array
    {
        return match ($dto->cipher) {
            'caesar' => $this->resolveCaesar($dto),
            'atbash' => $this->resolveAtbash($dto),
            'rot13', 'rot47', 'rot5' => $this->resolveRot($dto),
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
            'rot5' => $this->transformRot5($dto->text),
            default => null,
        };
    }

    /**
     * ROT5 is symmetric, so one transform is enough.
     *
     * @return array<string, mixed>
     */
    private function transformRot5(string $message): array
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
