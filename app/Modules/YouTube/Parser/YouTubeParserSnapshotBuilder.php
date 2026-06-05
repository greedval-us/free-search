<?php

namespace App\Modules\YouTube\Parser;

use App\Modules\YouTube\DTO\Parser\YouTubeParserCollectedDataDTO;
use App\Modules\YouTube\DTO\Parser\YouTubeParserSnapshotDTO;

final class YouTubeParserSnapshotBuilder
{
    public function build(string $videoId, YouTubeParserCollectedDataDTO $data): YouTubeParserSnapshotDTO
    {
        return new YouTubeParserSnapshotDTO(
            videoId: $videoId,
            commentsIndex: $data->commentsIndex(),
            repliesIndex: $data->repliesIndex(),
        );
    }
}
