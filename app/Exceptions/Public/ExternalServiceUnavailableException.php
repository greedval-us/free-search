<?php

namespace App\Exceptions\Public;

use App\Exceptions\PublicException;
use Throwable;

final class ExternalServiceUnavailableException extends PublicException
{
    public function __construct(
        string $translationKey,
        string $errorCode,
        ?Throwable $previous = null,
    ) {
        parent::__construct($translationKey, 503, $errorCode, previous: $previous);
    }
}
