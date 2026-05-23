<?php

namespace App\Modules\Telegram\Parser;

use App\Modules\ParserSupport\JsonRunStore;

class TelegramParserRunStore extends JsonRunStore
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
            'status' => 'running',
            'stage' => 'messages',
            'progress' => 1,
            'error' => null,
            'createdAt' => $now,
            'updatedAt' => $now,
            'context' => $context,
            'cursor' => [
                'messagesOffsetId' => 0,
                'messagesHasMore' => true,
                'messagesPage' => 0,
                'messagesTotalHint' => 0,
                'commentPostIds' => [],
                'commentPostIndex' => 0,
                'commentOffsetId' => 0,
                'nextAdvanceAt' => 0,
            ],
            'stats' => [
                'processedMessages' => 0,
                'processedComments' => 0,
            ],
            'data' => [
                'messages' => [],
                'messageIds' => [],
                'commentIds' => [],
                'commentsIndex' => [],
                'reactionsIndex' => [],
                'isChannel' => false,
            ],
            'result' => null,
        ];
    }

    protected function runPath(int $userId, string $runId): string
    {
        return sprintf('telegram-parser-runs/%d/%s.json', $userId, $runId);
    }
}
