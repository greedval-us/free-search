<?php

namespace App\Modules\Shifr\Actions;

use App\Modules\Shifr\Actions\Contracts\AtbashCipherActionInterface;
use App\Modules\Shifr\DTO\AtbashRequestDTO;
use App\Modules\Shifr\DTO\AtbashResultDTO;

class AtbashEncryptAction extends AbstractMirrorCipherAction implements AtbashCipherActionInterface
{
    public function execute(AtbashRequestDTO $dto): AtbashResultDTO
    {
        $result = $this->process($dto->message);

        return new AtbashResultDTO(
            original: $dto->message,
            result: $result,
        );
    }
}
