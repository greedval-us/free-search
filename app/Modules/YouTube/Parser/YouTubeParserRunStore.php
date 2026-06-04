<?php

namespace App\Modules\YouTube\Parser;

use App\Modules\ParserSupport\JsonRunStore;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\Modules\YouTube\Enums\YouTubeParserStage;

class YouTubeParserRunStore extends JsonRunStore
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
            'stage' => YouTubeParserStage::Comments->value,
            'progress' => 1,
            'error' => null,
            'createdAt' => $now,
            'updatedAt' => $now,
            'context' => $context,
            'cursor' => [
                'commentsPageToken' => '',
                'commentsPage' => 0,
                'commentsTotalHint' => 0,
                'replyThreadIds' => [],
                'replyThreadIndex' => 0,
                'replyPageToken' => '',
                'nextAdvanceAt' => 0,
            ],
            'stats' => [
                'processedComments' => 0,
                'processedReplies' => 0,
            ],
            'data' => [
                'commentIds' => [],
                'replyIds' => [],
                'threadParentMap' => [],
                'commentsIndex' => [],
                'repliesIndex' => [],
            ],
            'result' => null,
        ];
    }

    protected function runPath(int $userId, string $runId): string
    {
        return sprintf('youtube-parser-runs/%d/%s.json', $userId, $runId);
    }
}
