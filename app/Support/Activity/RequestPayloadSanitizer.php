<?php

namespace App\Support\Activity;

use Illuminate\Support\Str;

class RequestPayloadSanitizer
{
    /**
     * @var array<int, string>
     */
    private array $maskedKeys = [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'api_key',
    ];

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        $result = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (in_array($key, $this->maskedKeys, true)) {
                $result[$key] = '***';

                continue;
            }

            if (is_string($value) || is_numeric($value) || is_bool($value) || $value === null) {
                $result[$key] = is_string($value)
                    ? Str::limit(trim($value), 500)
                    : $value;

                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->sanitize($value);
            }
        }

        return $result;
    }
}

