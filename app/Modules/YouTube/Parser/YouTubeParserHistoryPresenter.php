<?php

namespace App\Modules\YouTube\Parser;

use App\Models\ParserRun;
use App\Modules\ParserSupport\ParserRunHistoryItemBuilder;

final class YouTubeParserHistoryPresenter
{
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
                $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
                $stats = is_array($payload['stats'] ?? null) ? $payload['stats'] : [];
                $result = is_array($payload['result'] ?? null) ? $payload['result'] : [];

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

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
