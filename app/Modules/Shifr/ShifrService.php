<?php

namespace App\Modules\Shifr;

use App\Modules\Shifr\Actions\AtbashDecryptAction;
use App\Modules\Shifr\Actions\AtbashEncryptAction;
use App\Modules\Shifr\Actions\CaesarDecryptAction;
use App\Modules\Shifr\Actions\CaesarEncryptAction;
use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;
use App\Modules\Shifr\DTO\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\CaesarCipherResultDTO;

class ShifrService
{
    public function __construct(
        private readonly CaesarEncryptAction $encryptCaesar,
        private readonly CaesarDecryptAction $decryptCaesar,
        private readonly AtbashEncryptAction $encryptAtbash,
        private readonly AtbashDecryptAction $decryptAtbash,
    ) {}

    public function encryptCaesar(string $message, int $shift): CaesarCipherResultDTO
    {
        $dto = new CaesarCipherRequestDTO([
            'message' => $message,
            'shift'   => $shift,
        ]);

        return $this->encryptCaesar->execute($dto);
    }

    public function decryptCaesar(string $message, int $shift): CaesarCipherResultDto
    {
        $dto = new CaesarCipherRequestDto([
            'message' => $message,
            'shift'   => $shift,
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
        $dto = new AtbashRequestDto([
            'message' => $message,
        ]);

        return $this->decryptAtbash->execute($dto);
    }

}
