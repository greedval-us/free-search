<?php

namespace App\Modules\Shifr\Actions\Classic;

use App\Modules\Shifr\Actions\Classic\Contracts\AtbashCipherActionInterface;
use App\Modules\Shifr\DTO\Classic\AtbashRequestDTO;
use App\Modules\Shifr\DTO\Classic\AtbashResultDTO;

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
