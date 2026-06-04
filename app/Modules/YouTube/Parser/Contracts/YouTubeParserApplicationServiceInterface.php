<?php

namespace App\Modules\YouTube\Parser\Contracts;

use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use App\Modules\YouTube\DTO\Request\YouTubeParserStartDTO;
use App\Modules\YouTube\DTO\Result\YouTubeCommentsResultDTO;
use App\Modules\YouTube\DTO\Result\YouTubeParserRunStatusDTO;

interface YouTubeParserApplicationServiceInterface
{
    public function comments(YouTubeCommentsQueryDTO $query): YouTubeCommentsResultDTO;

    public function start(YouTubeParserStartDTO $input): YouTubeParserRunStatusDTO;

    public function status(int $userId, string $runId): ?YouTubeParserRunStatusDTO;

    public function stop(int $userId, string $runId): ?YouTubeParserRunStatusDTO;

    /**
     * @return array<string, mixed>
     */
    public function getDownloadPayload(int $userId, string $runId): array;
}
