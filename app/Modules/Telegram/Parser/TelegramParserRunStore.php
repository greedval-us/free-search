<?php

namespace App\Modules\Telegram\Parser;

use App\Modules\ParserSupport\JsonRunStore;
use App\Modules\Telegram\Enums\TelegramParserStage;

class TelegramParserRunStore extends JsonRunStore
{
    protected function moduleKey(): string
    {
        return 'telegram';
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
            stage: TelegramParserStage::Messages->value,
            context: $context,
            cursor: [
                'messagesOffsetId' => 0,
                'messagesHasMore' => true,
                'messagesPage' => 0,
                'messagesTotalHint' => 0,
                'commentPostIds' => [],
                'commentPostIndex' => 0,
                'commentOffsetId' => 0,
            ],
            stats: [
                'processedMessages' => 0,
                'processedComments' => 0,
            ],
            data: [
                'messages' => [],
                'messageIds' => [],
                'commentIds' => [],
                'commentsIndex' => [],
                'reactionsIndex' => [],
                'isChannel' => false,
            ],
        );
    }
}
