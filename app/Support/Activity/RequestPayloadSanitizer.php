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
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'session',
        'recovery_code',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @var array<int, string>
     */
    private array $maskedKeyFragments = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
        'session',
        'recovery_code',
        'private_key',
        'client_secret',
        'api_key',
        'access_key',
        'otp',
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

            if ($this->shouldMaskKey($key)) {
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

    private function shouldMaskKey(string $key): bool
    {
        $normalized = strtolower(trim($key));

        if (in_array($normalized, $this->maskedKeys, true)) {
            return true;
        }

        foreach ($this->maskedKeyFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
