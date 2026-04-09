<?php

namespace App\Modules\Shifr\Actions;

use App\Modules\Shifr\DTO\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\CaesarCipherResultDTO;



class CaesarEncryptAction
{
    private string $latinLower = 'abcdefghijklmnopqrstuvwxyz';
    private string $latinUpper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private string $cyrLower   = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';
    private string $cyrUpper   = 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';

    public function execute(CaesarCipherRequestDTO $dto): CaesarCipherResultDTO
    {
        $result = $this->process($dto->message, $dto->shift);

        return new CaesarCipherResultDTO(
            original: $dto->message,
            shift: $dto->shift,
            result: $result,
        );
    }

    private function process(string $text, int $shift): string
    {
        $output = '';

        foreach (mb_str_split($text) as $char) {
            $output .= $this->shiftChar($char, $shift);
        }

        return $output;
    }

    private function shiftChar(string $char, int $shift): string
    {
        foreach ([
            $this->latinLower,
            $this->latinUpper,
            $this->cyrLower,
            $this->cyrUpper,
        ] as $alphabet) {

            if (mb_strpos($alphabet, $char) !== false) {
                return $this->shiftInsideAlphabet($char, $alphabet, $shift);
            }
        }

        return $char;
    }

    private function shiftInsideAlphabet(string $char, string $alphabet, int $shift): string
    {
        $len = mb_strlen($alphabet);
        $index = mb_strpos($alphabet, $char);

        $newIndex = ($index + $shift) % $len;
        if ($newIndex < 0) {
            $newIndex += $len;
        }

        return mb_substr($alphabet, $newIndex, 1);
    }
}
