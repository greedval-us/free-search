<?php

namespace App\Modules\Shifr\Application\Services;

use App\Modules\Shifr\Actions\Classic\ProcessClassicCipherAction;
use App\Modules\Shifr\Application\Contracts\ShifrClassicCipherServiceInterface;
use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;

final class ShifrClassicCipherService implements ShifrClassicCipherServiceInterface
{
    public function __construct(
        private readonly ProcessClassicCipherAction $processClassicCipherAction,
    ) {
    }

    public function process(ClassicCipherLookupDTO $dto): ?array
    {
        return $this->processClassicCipherAction->execute($dto);
    }
}
