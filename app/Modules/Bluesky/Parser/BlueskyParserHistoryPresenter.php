<?php

namespace App\Modules\Bluesky\Parser;

use App\Models\ParserRun;
use App\Modules\ParserSupport\Concerns\InteractsWithParserHistoryPayload;
use App\Modules\ParserSupport\ParserRunHistoryItemBuilder;

final class BlueskyParserHistoryPresenter
{
    use InteractsWithParserHistoryPayload;

    public function __construct(
        private readonly ParserRunHistoryItemBuilder $historyItemBuilder,
    ) {
    }

    /**
     * @param array<string, mixed>|null $run
     * @return array<string, mixed>
     */
    public function present(ParserRun $metadata, ?array $run): array
    {
        return $this->historyItemBuilder->build(
            metadata: $metadata,
            run: $run,
            excelRoute: BlueskyParserApplicationService::DOWNLOAD_EXCEL_ROUTE,
            jsonRoute: BlueskyParserApplicationService::DOWNLOAD_JSON_ROUTE,
            extra: function (?array $payload): array {
                $context = $this->context($payload);
                $stats = $this->stats($payload);
                $result = $this->result($payload);

                return [
                    'actor' => $this->stringValue(
                        $result['actor'] ?? $context['actor'] ?? null
                    ),
                    'processedPosts' => (int) ($stats['processedPosts'] ?? 0),
                    'processedAuthoredReplies' => (int) ($stats['processedAuthoredReplies'] ?? 0),
                    'processedReceivedReplies' => (int) ($stats['processedReceivedReplies'] ?? 0),
                    'processedFollowers' => (int) ($stats['processedFollowers'] ?? 0),
                    'processedFollows' => (int) ($stats['processedFollows'] ?? 0),
                    'processedReactions' => (int) ($stats['processedReactions'] ?? 0),
                ];
            }
        );
    }
}
