<?php

namespace App\Support\Observability;

use Illuminate\Support\Facades\Log;
use Throwable;

final class ExternalServiceLogger
{
    /**
     * @param array<string, mixed> $context
     */
    public function logMisconfiguration(string $service, string $operation, array $context = []): void
    {
        Log::warning('External service misconfigured', $this->baseContext(
            service: $service,
            operation: $operation,
            context: $context,
        ));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function logConnectionFailure(
        string $service,
        string $operation,
        Throwable $exception,
        array $context = []
    ): void {
        Log::warning('External service connection failed', $this->baseContext(
            service: $service,
            operation: $operation,
            context: $context,
            extra: [
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ],
        ));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function logHttpFailure(
        string $service,
        string $operation,
        int $status,
        array $context = [],
        ?string $responseBody = null
    ): void {
        $extra = ['status' => $status];

        $snippet = $this->bodySnippet($responseBody);
        if ($snippet !== null) {
            $extra['response_body_preview'] = $snippet;
        }

        Log::warning('External service request failed', $this->baseContext(
            service: $service,
            operation: $operation,
            context: $context,
            extra: $extra,
        ));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function logFallback(
        string $service,
        string $operation,
        string $reason,
        array $context = []
    ): void {
        Log::info('External service fallback used', $this->baseContext(
            service: $service,
            operation: $operation,
            context: $context,
            extra: ['reason' => $reason],
        ));
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function baseContext(
        string $service,
        string $operation,
        array $context = [],
        array $extra = []
    ): array {
        return [
            'service' => $service,
            'operation' => $operation,
            'context' => $this->sanitize($context),
            ...$extra,
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower($key);

            if ($this->isSensitiveKey($normalizedKey)) {
                $sanitized[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeNested($value);

                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = mb_strlen($value) > 300
                    ? mb_substr($value, 0, 300) . '...'
                    : $value;

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * @param array<int|string, mixed> $value
     * @return array<int|string, mixed>
     */
    private function sanitizeNested(array $value): array
    {
        $result = [];

        foreach ($value as $nestedKey => $nestedValue) {
            $normalizedKey = is_string($nestedKey) ? strtolower($nestedKey) : (string) $nestedKey;

            if ($this->isSensitiveKey($normalizedKey)) {
                $result[$nestedKey] = '[redacted]';

                continue;
            }

            if (is_array($nestedValue)) {
                $result[$nestedKey] = $this->sanitizeNested($nestedValue);

                continue;
            }

            if (is_string($nestedValue)) {
                $result[$nestedKey] = mb_strlen($nestedValue) > 300
                    ? mb_substr($nestedValue, 0, 300) . '...'
                    : $nestedValue;

                continue;
            }

            $result[$nestedKey] = $nestedValue;
        }

        return $result;
    }

    private function isSensitiveKey(string $key): bool
    {
        return str_contains($key, 'token')
            || str_contains($key, 'password')
            || str_contains($key, 'secret')
            || str_contains($key, 'authorization')
            || str_contains($key, 'apikey')
            || $key === 'key';
    }

    private function bodySnippet(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/', ' ', $body) ?? '');

        if ($normalized === '') {
            return null;
        }

        return mb_strlen($normalized) > 300
            ? mb_substr($normalized, 0, 300) . '...'
            : $normalized;
    }
}
