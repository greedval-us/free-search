<?php

namespace App\Http\Controllers\Gdelt;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseGdeltController extends Controller
{
    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonPayload(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }
}
