<?php

namespace App\Http\Controllers\Concerns;

use Throwable;

trait ResolvesHttpStatusCodeFromException
{
    protected function statusCodeFromException(Throwable $exception, int $fallback = 422): int
    {
        $code = $exception->getCode();

        return is_int($code) && $code >= 400 && $code < 600 ? $code : $fallback;
    }
}

