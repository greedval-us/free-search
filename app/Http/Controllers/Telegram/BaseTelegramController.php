<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseTelegramController extends Controller
{
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

    protected function userId(Request $request): int
    {
        return (int) $request->user()->id;
    }
}
