<?php

namespace App\Modules\Bluesky\Parser;

use App\Modules\Bluesky\Parser\Enums\BlueskyParserStage;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\Modules\ParserSupport\JsonRunStore;

final class BlueskyParserRunStore extends JsonRunStore
{
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function initialState(int $userId, string $runId, array $context, string $now): array
    {
        return [
            'runId' => $runId,
            'userId' => $userId,
            'status' => ParserRunStatus::Running->value,
            'stage' => BlueskyParserStage::Profile->value,
            'progress' => 1,
            'error' => null,
            'createdAt' => $now,
            'updatedAt' => $now,
            'context' => $context,
            'cursor' => [
                'feedCursor' => '',
                'feedPage' => 0,
                'followersCursor' => '',
                'followersPage' => 0,
                'followsCursor' => '',
                'followsPage' => 0,
                'interactionPostIndex' => 0,
                'interactionKind' => 'likes',
                'interactionCursor' => '',
                'nextAdvanceAt' => 0,
            ],
            'stats' => [
                'processedPosts' => 0,
                'processedAuthoredReplies' => 0,
                'processedReceivedReplies' => 0,
                'processedFollowers' => 0,
                'processedFollows' => 0,
                'processedReactions' => 0,
            ],
            'data' => [
                'profile' => null,
                'postIds' => [],
                'authoredReplyIds' => [],
                'receivedReplyIds' => [],
                'followersIds' => [],
                'followsIds' => [],
                'reactionIds' => [],
                'postsIndex' => [],
                'authoredRepliesIndex' => [],
                'receivedRepliesIndex' => [],
                'followersIndex' => [],
                'followsIndex' => [],
                'reactionsIndex' => [],
            ],
            'result' => null,
        ];
    }

    protected function runPath(int $userId, string $runId): string
    {
        return sprintf('bluesky-parser-runs/%d/%s.json', $userId, $runId);
    }
}
