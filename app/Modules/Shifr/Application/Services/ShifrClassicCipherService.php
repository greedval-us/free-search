<?php

namespace App\Modules\Shifr\Application\Services;

use App\Modules\Shifr\Actions\Classic\ProcessClassicCipherAction;
use App\Modules\Shifr\Application\Contracts\ShifrClassicCipherServiceInterface;
use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;
use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;

final class ShifrClassicCipherService implements ShifrClassicCipherServiceInterface
{
    public function __construct(
        private readonly ProcessClassicCipherAction $processClassicCipherAction,
    ) {
    }

    public function process(ClassicCipherLookupDTO $dto): ?ShifrResultDataInterface
    {
        return $this->processClassicCipherAction->execute($dto);
    }
}
