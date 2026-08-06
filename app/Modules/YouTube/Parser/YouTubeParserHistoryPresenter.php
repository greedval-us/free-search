<?php

namespace App\Modules\YouTube\Parser;

use App\Models\ParserRun;
use App\Modules\ParserSupport\Concerns\InteractsWithParserHistoryPayload;
use App\Modules\ParserSupport\ParserRunHistoryItemBuilder;

final class YouTubeParserHistoryPresenter
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
            excelRoute: YouTubeParserApplicationService::DOWNLOAD_EXCEL_ROUTE,
            jsonRoute: YouTubeParserApplicationService::DOWNLOAD_JSON_ROUTE,
            extra: function (?array $payload): array {
                $context = $this->context($payload);
                $stats = $this->stats($payload);
                $result = $this->result($payload);

                return [
                    'videoId' => $this->stringValue(
                        $result['videoId'] ?? $context['videoId'] ?? null
                    ),
                    'processedComments' => (int) ($stats['processedComments'] ?? 0),
                    'processedReplies' => (int) ($stats['processedReplies'] ?? 0),
                ];
            }
        );
    }
}
