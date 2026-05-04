<?php

namespace App\Modules\Shifr\Actions\Contracts;

use App\Modules\Shifr\DTO\CaesarCipherRequestDTO;
use App\Modules\Shifr\DTO\CaesarCipherResultDTO;

interface CaesarCipherActionInterface
{
    public function execute(CaesarCipherRequestDTO $dto): CaesarCipherResultDTO;
}

