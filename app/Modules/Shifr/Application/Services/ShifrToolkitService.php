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

final class ShifrToolkitService implements ShifrToolkitServiceInterface
{
    public function __construct(
        private readonly ComputeHashAction $computeHashAction,
        private readonly TransformTextAction $transformTextAction,
        private readonly ExtractIocsAction $extractIocsAction,
        private readonly InspectJwtAction $inspectJwtAction,
    ) {
    }

    public function hash(HashLookupDTO $dto): array
    {
        return $this->computeHashAction->execute($dto);
    }

    public function transform(TransformLookupDTO $dto): array
    {
        return $this->transformTextAction->execute($dto);
    }

    public function extractIocs(IocLookupDTO $dto): array
    {
        return $this->extractIocsAction->execute($dto);
    }

    public function inspectJwt(JwtLookupDTO $dto): array
    {
        return $this->inspectJwtAction->execute($dto);
    }
}
