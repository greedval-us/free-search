<?php

namespace Tests\Fixtures\Parser;

use App\Modules\ParserSupport\JsonRunStore;

final class AdditionalParserRunStore extends JsonRunStore
{
    protected function moduleKey(): string
    {
        return 'additional-source';
    }

    protected function initialState(int $userId, string $runId, array $context, string $now): array
    {
        return $this->buildInitialState(
            userId: $userId,
            runId: $runId,
            now: $now,
            stage: 'items',
            context: $context,
            cursor: [],
            stats: [],
            data: [],
        );
    }
}
