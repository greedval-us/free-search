<?php

namespace App\Modules\Shifr\Actions\Classic\Contracts;

use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;
use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;

interface ClassicCipherProcessorInterface
{
    public function supports(string $cipher): bool;

    public function process(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface;
}
