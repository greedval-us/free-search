<?php

namespace App\Modules\ParserSupport\Concerns;

trait InteractsWithParserHistoryPayload
{
    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    protected function context(?array $payload): array
    {
        return $this->payloadSection($payload, 'context');
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    protected function stats(?array $payload): array
    {
        return $this->payloadSection($payload, 'stats');
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    protected function result(?array $payload): array
    {
        return $this->payloadSection($payload, 'result');
    }

    protected function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    private function payloadSection(?array $payload, string $key): array
    {
        return is_array($payload[$key] ?? null) ? $payload[$key] : [];
    }
}
