<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Contracts\ArrayPayloadable;
use Illuminate\Http\JsonResponse;

trait HandlesArrayPayloadResponses
{
    protected function jsonPayloadFrom(ArrayPayloadable $payload): JsonResponse
    {
        return $this->jsonPayload($payload->toArray());
    }

    protected function jsonPayloadFromOrNotFound(?ArrayPayloadable $payload): JsonResponse
    {
        abort_unless($payload !== null, 404);

        return $this->jsonPayloadFrom($payload);
    }
}

