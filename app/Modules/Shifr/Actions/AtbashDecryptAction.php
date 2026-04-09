<?php

namespace App\Modules\Shifr\Actions;

use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;

class AtbashDecryptAction
{
    private string $latinLower = 'abcdefghijklmnopqrstuvwxyz';
    private string $latinUpper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private string $cyrLower   = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';
    private string $cyrUpper   = 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';

    public function execute(AtbashRequestDTO $dto): AtbashResultDTO
    {
        // В Atbash расшифровка идентична шифрованию
        $result = $this->process($dto->message);

        return new AtbashResultDto(
            original: $dto->message,
            result: $result
        );
    }

    private function process(string $text): string
    {
        $output = '';

        foreach (mb_str_split($text) as $char) {
            $output .= $this->invertChar($char);
        }

        return $output;
    }

    private function invertChar(string $char): string
    {
        foreach ([
            $this->latinLower,
            $this->latinUpper,
            $this->cyrLower,
            $this->cyrUpper,
        ] as $alphabet) {

            $pos = mb_strpos($alphabet, $char);

            if ($pos !== false) {
                $len = mb_strlen($alphabet);
                $inverseIndex = $len - $pos - 1;

                return mb_substr($alphabet, $inverseIndex, 1);
            }
        }

        return $char;
    }
}
