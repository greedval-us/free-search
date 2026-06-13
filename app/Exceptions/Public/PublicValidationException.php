<?php

namespace App\Exceptions\Public;

use App\Exceptions\PublicException;

final class PublicValidationException extends PublicException
{
    public function __construct(
        string $translationKey,
        string $errorCode,
        int $status = 422,
    ) {
        parent::__construct($translationKey, $status, $errorCode);
    }
}
