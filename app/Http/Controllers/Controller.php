<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesHtmlReports;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    use HandlesHtmlReports;

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonPayload(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonOk(array $payload = [], int $status = 200): JsonResponse
    {
        return $this->jsonPayload(array_merge(['ok' => true], $payload), $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonError(string $message, int $status = 422, array $payload = []): JsonResponse
    {
        return $this->jsonPayload(array_merge([
            'ok' => false,
            'message' => $message,
        ], $payload), $status);
    }

    protected function applyRequestLocale(string $locale): void
    {
        app()->setLocale($locale);
    }
}
