<?php

namespace App\Modules\Shifr\Actions\Classic\Contracts;

use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;

interface ClassicCipherProcessorInterface
{
    public function supports(string $cipher): bool;

    /**
     * @return array<string, mixed>|null
     */
    public function process(ClassicCipherLookupDTO $dto): ?array;
}
