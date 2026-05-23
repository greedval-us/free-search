<?php

namespace App\Modules\Shifr\Application\Contracts;

use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;
use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;

interface ShifrClassicCipherServiceInterface
{
    public function process(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface;
}
