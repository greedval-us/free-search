<?php

namespace App\Modules\Fio\Domain\Services;

final class FioNameNormalizer
{
    public function normalize(string $fullName): string
    {
        $value = trim($fullName);
        $value = preg_replace('/\s+/u', ' ', $value);

        return is_string($value) ? $value : '';
    }
}
