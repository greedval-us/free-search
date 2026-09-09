<?php

namespace App\Modules\Bluesky\Parser;

use App\Modules\Bluesky\Enums\BlueskyParserInteractionKind;
use App\Modules\Bluesky\Enums\BlueskyParserStage;
use App\Modules\ParserSupport\JsonRunStore;

final class BlueskyParserRunStore extends JsonRunStore
{
    protected function moduleKey(): string
    {
        return 'bluesky';
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function initialState(int $userId, string $runId, array $context, string $now): array
    {
        return $this->buildInitialState(
            userId: $userId,
            runId: $runId,
            now: $now,
            stage: BlueskyParserStage::Profile->value,
            context: $context,
            cursor: [
                'feedCursor' => '',
                'feedPage' => 0,
                'followersCursor' => '',
                'followersPage' => 0,
                'followsCursor' => '',
                'followsPage' => 0,
                'interactionPostIndex' => 0,
                'interactionKind' => BlueskyParserInteractionKind::Likes->value,
                'interactionCursor' => '',
            ],
            stats: [
                'processedPosts' => 0,
                'processedAuthoredReplies' => 0,
                'processedReceivedReplies' => 0,
                'processedFollowers' => 0,
                'processedFollows' => 0,
                'processedReactions' => 0,
            ],
            data: [
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
        );
    }
}
