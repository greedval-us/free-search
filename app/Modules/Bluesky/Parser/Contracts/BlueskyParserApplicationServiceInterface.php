<?php

namespace App\Modules\Bluesky\Parser\Contracts;

use App\Modules\Bluesky\DTO\Request\BlueskyParserStartDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyParserRunStatusDTO;

interface BlueskyParserApplicationServiceInterface
{
    public function start(BlueskyParserStartDTO $input): BlueskyParserRunStatusDTO;

    public function status(int $userId, string $runId): ?BlueskyParserRunStatusDTO;

    public function stop(int $userId, string $runId): ?BlueskyParserRunStatusDTO;

    /**
     * @return array<string, mixed>
     */
    public function getDownloadPayload(int $userId, string $runId): array;
}
