<?php

namespace App\Modules\ParserSupport\Contracts;

use App\Support\Contracts\ArrayPayloadable;

interface ParserRunApplicationServiceInterface
{
    public function status(int $userId, string $runId): ?ArrayPayloadable;

    public function stop(int $userId, string $runId): ?ArrayPayloadable;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $userId): array;

    /**
     * @return array<string, mixed>
     */
    public function getDownloadPayload(int $userId, string $runId): array;
}
