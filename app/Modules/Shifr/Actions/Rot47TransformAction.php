<?php

namespace App\Modules\Shifr\Actions;

use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;

final class Rot47TransformAction
{
    public function execute(AtbashRequestDTO $dto): AtbashResultDTO
    {
        return new AtbashResultDTO(
            original: $dto->message,
            result: $this->transform($dto->message),
        );
    }

    private function transform(string $text): string
    {
        $result = '';
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];
            $ord = ord($char);

            if ($ord >= 33 && $ord <= 126) {
                $result .= chr(33 + (($ord - 33 + 47) % 94));
                continue;
            }

            $result .= $char;
        }

        return $result;
    }
}

