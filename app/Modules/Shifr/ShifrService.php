<?php

namespace App\Modules\Shifr;

use App\Modules\Shifr\Actions\AtbashDecryptAction;
use App\Modules\Shifr\Actions\AtbashEncryptAction;
use App\Modules\Shifr\Actions\CaesarDecryptAction;
use App\Modules\Shifr\Actions\CaesarEncryptAction;
use App\Modules\Shifr\Actions\Rot13TransformAction;
use App\Modules\Shifr\Actions\Rot47TransformAction;
use App\Modules\Shifr\Contracts\ShifrServiceInterface;
use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;
use App\Modules\Shifr\DTO\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\CaesarCipherResultDTO;

class ShifrService implements ShifrServiceInterface
{
    public function __construct(
        private readonly CaesarEncryptAction $encryptCaesar,
        private readonly CaesarDecryptAction $decryptCaesar,
        private readonly AtbashEncryptAction $encryptAtbash,
        private readonly AtbashDecryptAction $decryptAtbash,
        private readonly Rot13TransformAction $rot13Transform,
        private readonly Rot47TransformAction $rot47Transform,
    ) {}

    public function encryptCaesar(string $message, int $shift): CaesarCipherResultDTO
    {
        $dto = new CaesarCipherRequestDTO([
            'message' => $message,
            'shift' => $shift,
        ]);

        return $this->encryptCaesar->execute($dto);
    }

    public function decryptCaesar(string $message, int $shift): CaesarCipherResultDTO
    {
        $dto = new CaesarCipherRequestDTO([
            'message' => $message,
            'shift' => $shift,
        ]);

        return $this->decryptCaesar->execute($dto);
    }

    public function encryptAtbash(string $message): AtbashResultDTO
    {
        $dto = new AtbashRequestDTO([
            'message' => $message,
        ]);

        return $this->encryptAtbash->execute($dto);
    }

    public function decryptAtbash(string $message): AtbashResultDTO
    {
        $dto = new AtbashRequestDTO([
            'message' => $message,
        ]);

        return $this->decryptAtbash->execute($dto);
    }

    public function transformRot13(string $message): AtbashResultDTO
    {
        $dto = new AtbashRequestDTO([
            'message' => $message,
        ]);

        return $this->rot13Transform->execute($dto);
    }

    public function transformRot47(string $message): AtbashResultDTO
    {
        $dto = new AtbashRequestDTO([
            'message' => $message,
        ]);

        return $this->rot47Transform->execute($dto);
    }
}
