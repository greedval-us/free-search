<?php

namespace App\Modules\Shifr\Application\Services;

use App\Modules\Shifr\Actions\Toolkit\ComputeHashAction;
use App\Modules\Shifr\Actions\Toolkit\ExtractIocsAction;
use App\Modules\Shifr\Actions\Toolkit\InspectJwtAction;
use App\Modules\Shifr\Actions\Toolkit\TransformTextAction;
use App\Modules\Shifr\Application\Contracts\ShifrToolkitServiceInterface;
use App\Modules\Shifr\DTO\Toolkit\HashLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\IocLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\JwtLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\TransformLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\HashResultDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\IocExtractResultDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\JwtInspectResultDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\TransformResultDTO;

final class ShifrToolkitService implements ShifrToolkitServiceInterface
{
    public function __construct(
        private readonly ComputeHashAction $computeHashAction,
        private readonly TransformTextAction $transformTextAction,
        private readonly ExtractIocsAction $extractIocsAction,
        private readonly InspectJwtAction $inspectJwtAction,
    ) {
    }

    public function hash(HashLookupDTO $dto): HashResultDTO
    {
        return $this->computeHashAction->execute($dto);
    }

    public function transform(TransformLookupDTO $dto): TransformResultDTO
    {
        return $this->transformTextAction->execute($dto);
    }

    public function extractIocs(IocLookupDTO $dto): IocExtractResultDTO
    {
        return $this->extractIocsAction->execute($dto);
    }

    public function inspectJwt(JwtLookupDTO $dto): JwtInspectResultDTO
    {
        return $this->inspectJwtAction->execute($dto);
    }
}
