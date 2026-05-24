<?php

namespace App\Modules\Shifr\Actions\Classic\Contracts;

use App\Modules\Shifr\DTO\Classic\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\Classic\CaesarCipherResultDTO;

interface CaesarCipherActionInterface
{
    public function execute(CaesarCipherRequestDTO $dto): CaesarCipherResultDTO;
}
