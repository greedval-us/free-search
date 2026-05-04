<?php

namespace App\Modules\Shifr\Actions;

use App\Modules\Shifr\Actions\Contracts\CaesarCipherActionInterface;
use App\Modules\Shifr\DTO\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\CaesarCipherResultDTO;

class CaesarDecryptAction extends AbstractShiftCipherAction implements CaesarCipherActionInterface
{
    public function execute(CaesarCipherRequestDTO $dto): CaesarCipherResultDTO
    {
        $result = $this->process($dto->message, -$dto->shift);

        return new CaesarCipherResultDTO(
            original: $dto->message,
            shift: $dto->shift,
            result: $result,
        );
    }
}
