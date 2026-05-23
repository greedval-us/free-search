<?php

namespace App\Modules\Shifr\Actions\Classic\Contracts;

use App\Modules\Shifr\DTO\Classic\AtbashRequestDTO;
use App\Modules\Shifr\DTO\Classic\AtbashResultDTO;

interface AtbashCipherActionInterface
{
    public function execute(AtbashRequestDTO $dto): AtbashResultDTO;
}
