<?php

namespace App\Modules\Shifr\Contracts;

use App\Modules\Shifr\DTO\AtbashResultDTO;
use App\Modules\Shifr\DTO\CaesarCipherResultDTO;
use App\Modules\Shifr\DTO\ClassicCipherLookupDTO;

interface ShifrServiceInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function processClassic(ClassicCipherLookupDTO $dto): ?array;

    public function encryptCaesar(string $message, int $shift): CaesarCipherResultDTO;

    public function decryptCaesar(string $message, int $shift): CaesarCipherResultDTO;

    public function encryptAtbash(string $message): AtbashResultDTO;

    public function decryptAtbash(string $message): AtbashResultDTO;

    public function transformRot13(string $message): AtbashResultDTO;

    public function transformRot47(string $message): AtbashResultDTO;

    public function transformRot5(string $message): AtbashResultDTO;

    public function encryptVigenere(string $message, string $key): AtbashResultDTO;

    public function decryptVigenere(string $message, string $key): AtbashResultDTO;

    public function encryptRailFence(string $message, int $rails): AtbashResultDTO;

    public function decryptRailFence(string $message, int $rails): AtbashResultDTO;

    public function encryptXor(string $message, string $key): AtbashResultDTO;

    public function decryptXor(string $message, string $key): AtbashResultDTO;

    public function encryptAffine(string $message, int $a, int $b): AtbashResultDTO;

    public function decryptAffine(string $message, int $a, int $b): AtbashResultDTO;

    public function encryptPlayfair(string $message, string $key): AtbashResultDTO;

    public function decryptPlayfair(string $message, string $key): AtbashResultDTO;

    public function encryptColumnar(string $message, string $key): AtbashResultDTO;

    public function decryptColumnar(string $message, string $key): AtbashResultDTO;

    public function encryptMorse(string $message, string $separator): AtbashResultDTO;

    public function decryptMorse(string $message, string $separator): AtbashResultDTO;
}
