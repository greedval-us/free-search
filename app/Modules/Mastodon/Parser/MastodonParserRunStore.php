<?php

namespace App\Modules\Mastodon\Parser;

use App\Modules\ParserSupport\JsonRunStore;

final class MastodonParserRunStore extends JsonRunStore
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
            'stage' => 'statuses',
            'progress' => 1,
            'error' => null,
            'createdAt' => $now,
            'updatedAt' => $now,
            'context' => $context,
            'cursor' => [
                'statusesMaxId' => '',
                'statusesPage' => 0,
                'statusesTotalHint' => 0,
                'commentStatusIds' => [],
                'commentStatusIndex' => 0,
                'nextAdvanceAt' => 0,
            ],
            'stats' => [
                'processedStatuses' => 0,
                'processedComments' => 0,
            ],
            'data' => [
                'account' => null,
                'statusIds' => [],
                'commentIds' => [],
                'statusesIndex' => [],
                'commentsIndex' => [],
            ],
            'result' => null,
        ];
    }

    protected function runPath(int $userId, string $runId): string
    {
        return sprintf('mastodon-parser-runs/%d/%s.json', $userId, $runId);
    }
}
