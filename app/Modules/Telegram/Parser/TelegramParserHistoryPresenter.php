<?php

namespace App\Modules\Telegram\Parser;

use App\Models\ParserRun;
use App\Modules\ParserSupport\Concerns\InteractsWithParserHistoryPayload;
use App\Modules\ParserSupport\ParserRunHistoryItemBuilder;

final class TelegramParserHistoryPresenter
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
            excelRoute: TelegramParserApplicationService::DOWNLOAD_EXCEL_ROUTE,
            jsonRoute: TelegramParserApplicationService::DOWNLOAD_JSON_ROUTE,
            extra: function (?array $payload): array {
                $context = $this->context($payload);
                $stats = $this->stats($payload);
                $result = $this->result($payload);

                return [
                    'chatUsername' => $this->stringValue(
                        $result['chatUsername'] ?? $context['chatUsername'] ?? null
                    ),
                    'keyword' => $this->stringValue($context['keyword'] ?? null),
                    'period' => $this->stringValue($context['period'] ?? null),
                    'processedMessages' => (int) ($stats['processedMessages'] ?? 0),
                    'processedComments' => (int) ($stats['processedComments'] ?? 0),
                ];
            }
        );
    }
}
