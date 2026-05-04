<?php

namespace App\Modules\Shifr\Contracts;

use App\Modules\Shifr\DTO\AtbashResultDTO;
use App\Modules\Shifr\DTO\CaesarCipherResultDTO;

interface ShifrServiceInterface
{
    public function encryptCaesar(string $message, int $shift): CaesarCipherResultDTO;

    public function decryptCaesar(string $message, int $shift): CaesarCipherResultDTO;

    public function encryptAtbash(string $message): AtbashResultDTO;

    public function decryptAtbash(string $message): AtbashResultDTO;
}

