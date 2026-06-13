<?php

namespace App\Exceptions\Public;

use App\Exceptions\PublicException;

final class IntegrationMisconfiguredException extends PublicException
{
    public function __construct(string $translationKey, string $errorCode)
    {
        parent::__construct($translationKey, 503, $errorCode);
    }
}
