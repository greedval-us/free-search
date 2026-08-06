<?php

namespace App\Modules\Mastodon\Parser;

use App\Models\ParserRun;
use App\Modules\ParserSupport\ParserRunHistoryItemBuilder;

final class MastodonParserHistoryPresenter
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
            excelRoute: MastodonParserApplicationService::DOWNLOAD_EXCEL_ROUTE,
            jsonRoute: MastodonParserApplicationService::DOWNLOAD_JSON_ROUTE,
            extra: function (?array $payload): array {
                $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
                $stats = is_array($payload['stats'] ?? null) ? $payload['stats'] : [];
                $result = is_array($payload['result'] ?? null) ? $payload['result'] : [];

                return [
                    'account' => $this->stringValue(
                        $result['account'] ?? $context['account'] ?? null
                    ),
                    'processedStatuses' => (int) ($stats['processedStatuses'] ?? 0),
                    'processedComments' => (int) ($stats['processedComments'] ?? 0),
                ];
            }
        );
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
