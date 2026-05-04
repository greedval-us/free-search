<?php

namespace App\Modules\Shifr\Application\Contracts;

use App\Modules\Shifr\DTO\Toolkit\HashLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\IocLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\TransformLookupDTO;

interface ShifrToolkitServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function hash(HashLookupDTO $dto): array;

    /**
     * @return array<string, mixed>
     */
    public function transform(TransformLookupDTO $dto): array;

    /**
     * @return array<string, mixed>
     */
    public function extractIocs(IocLookupDTO $dto): array;
}
