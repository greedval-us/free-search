<?php

namespace App\Modules\YouTube\Parser;

use App\Modules\ParserSupport\JsonRunStore;
use App\Modules\YouTube\Enums\YouTubeParserStage;

class YouTubeParserRunStore extends JsonRunStore
{
    protected function moduleKey(): string
    {
        return 'youtube';
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function initialState(int $userId, string $runId, array $context, string $now): array
    {
        return $this->buildInitialState(
            userId: $userId,
            runId: $runId,
            now: $now,
            stage: YouTubeParserStage::Comments->value,
            context: $context,
            cursor: [
                'commentsPageToken' => '',
                'commentsPage' => 0,
                'commentsTotalHint' => 0,
                'replyThreadIds' => [],
                'replyThreadIndex' => 0,
                'replyPageToken' => '',
            ],
            stats: [
                'processedComments' => 0,
                'processedReplies' => 0,
            ],
            data: [
                'commentIds' => [],
                'replyIds' => [],
                'threadParentMap' => [],
                'commentsIndex' => [],
                'repliesIndex' => [],
            ],
        );
    }
}
