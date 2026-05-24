<?php

namespace App\Modules\Shifr\Actions\Classic;

use App\Modules\Shifr\Actions\Classic\Contracts\CaesarCipherActionInterface;
use App\Modules\Shifr\DTO\Classic\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\Classic\CaesarCipherResultDTO;

class CaesarEncryptAction extends AbstractShiftCipherAction implements CaesarCipherActionInterface
{
    public function execute(CaesarCipherRequestDTO $dto): CaesarCipherResultDTO
    {
        $result = $this->process($dto->message, $dto->shift);

        return new CaesarCipherResultDTO(
            original: $dto->message,
            shift: $dto->shift,
            result: $result,
        );
    }
}
