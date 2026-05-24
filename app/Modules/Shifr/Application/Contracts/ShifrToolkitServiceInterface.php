<?php

namespace App\Modules\Shifr\Application\Contracts;

use App\Modules\Shifr\DTO\Toolkit\HashLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\IocLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\JwtLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\TransformLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\HashResultDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\IocExtractResultDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\JwtInspectResultDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\TransformResultDTO;

interface ShifrToolkitServiceInterface
{
    public function hash(HashLookupDTO $dto): HashResultDTO;

    public function transform(TransformLookupDTO $dto): TransformResultDTO;

    public function extractIocs(IocLookupDTO $dto): IocExtractResultDTO;

    public function inspectJwt(JwtLookupDTO $dto): JwtInspectResultDTO;
}
