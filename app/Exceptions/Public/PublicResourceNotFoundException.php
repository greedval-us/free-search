<?php

namespace App\Exceptions\Public;

use App\Exceptions\PublicException;

final class PublicResourceNotFoundException extends PublicException
{
    public function __construct(string $translationKey, string $errorCode)
    {
        parent::__construct($translationKey, 404, $errorCode);
    }
}
