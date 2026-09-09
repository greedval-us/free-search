<?php

namespace App\Modules\Mastodon\Parser;

use App\Modules\Mastodon\Enums\MastodonParserStage;
use App\Modules\ParserSupport\JsonRunStore;

final class MastodonParserRunStore extends JsonRunStore
{
    protected function moduleKey(): string
    {
        return 'mastodon';
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
            stage: MastodonParserStage::Statuses->value,
            context: $context,
            cursor: [
                'statusesMaxId' => '',
                'statusesPage' => 0,
                'statusesTotalHint' => 0,
                'commentStatusIds' => [],
                'commentStatusIndex' => 0,
            ],
            stats: [
                'processedStatuses' => 0,
                'processedComments' => 0,
            ],
            data: [
                'account' => null,
                'statusIds' => [],
                'commentIds' => [],
                'statusesIndex' => [],
                'commentsIndex' => [],
            ],
        );
    }
}
