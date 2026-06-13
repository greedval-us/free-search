<?php

namespace App\Exceptions\Public;

use App\Exceptions\PublicException;

final class ExternalServiceRequestException extends PublicException
{
    public function __construct(
        string $translationKey,
        int $status,
        string $errorCode,
    ) {
        parent::__construct($translationKey, $status, $errorCode);
    }
}
