<?php

namespace App\Modules\Shifr\Application\Contracts;

use App\Modules\Shifr\DTO\ClassicCipherLookupDTO;

interface ShifrClassicCipherServiceInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function process(ClassicCipherLookupDTO $dto): ?array;
}

