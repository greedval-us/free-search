<?php

namespace App\Modules\Bluesky\Parser;

use App\Modules\Bluesky\DTO\Parser\BlueskyParserCollectedDataDTO;
use App\Modules\Bluesky\DTO\Parser\BlueskyParserSnapshotDTO;

final class BlueskyParserSnapshotBuilder
{
    public function build(string $actorQuery, BlueskyParserCollectedDataDTO $data): BlueskyParserSnapshotDTO
    {
        return new BlueskyParserSnapshotDTO(
            actor: $actorQuery,
            resolvedActor: $data->profile(),
            postsIndex: $data->postsIndex(),
            authoredRepliesIndex: $data->authoredRepliesIndex(),
            receivedRepliesIndex: $data->receivedRepliesIndex(),
            followersIndex: $data->followersIndex(),
            followsIndex: $data->followsIndex(),
            reactionsIndex: $data->reactionsIndex(),
        );
    }
}
