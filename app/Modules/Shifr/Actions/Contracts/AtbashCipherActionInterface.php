<?php

namespace App\Modules\Shifr\Actions\Contracts;

use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;

interface AtbashCipherActionInterface
{
    public function execute(AtbashRequestDTO $dto): AtbashResultDTO;
}

