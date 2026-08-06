<?php

namespace App\Modules\Telegram\Parser;

use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\Telegram\Parser\Contracts\TelegramParserExportBuilderInterface;
use App\Modules\Telegram\Support\TelegramConfig;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TelegramParserExportBuilder implements TelegramParserExportBuilderInterface
{
    public function __construct(private readonly TelegramConfig $config)
    {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, SheetDefinition>
     */
    public function buildSheets(array $payload): array
    {
        return [
            $this->buildSummarySheet($payload),
            $this->buildMessagesSheet($payload),
            $this->buildCommentsSheet($payload),
            $this->buildReactionsSheet($payload),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildSummarySheet(array $payload): SheetDefinition
    {
        $range = is_array($payload['range'] ?? null) ? $payload['range'] : [];
        $messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
        $comments = is_array($payload['commentsIndex'] ?? null) ? $payload['commentsIndex'] : [];
        $chatUsername = trim((string) ($payload['chatUsername'] ?? ''));

        return new SheetDefinition(
            title: (string) __('exports.telegram.sheets.summary'),
            headings: $this->translations('exports.telegram.summary.headings'),
            rows: [
                [(string) __('exports.telegram.summary.source'), 'Telegram'],
                [(string) __('exports.telegram.summary.channel'), $chatUsername],
                [(string) __('exports.telegram.summary.channel_url'), $this->buildChatUrl($chatUsername)],
                [(string) __('exports.telegram.summary.period'), (string) ($payload['period'] ?? '')],
                [(string) __('exports.telegram.summary.keyword'), (string) ($payload['keyword'] ?? '')],
                [(string) __('exports.telegram.summary.date_from'), (string) ($range['dateFrom'] ?? '')],
                [(string) __('exports.telegram.summary.date_to'), (string) ($range['dateTo'] ?? '')],
                [(string) __('exports.telegram.summary.is_channel'), (bool) ($payload['isChannel'] ?? false) ? __('exports.common.yes') : __('exports.common.no')],
                [(string) __('exports.telegram.summary.messages'), (int) ($payload['messagesCount'] ?? 0)],
                [(string) __('exports.telegram.summary.comments'), (int) ($payload['commentsCount'] ?? 0)],
                [(string) __('exports.telegram.summary.messages_with_media'), $this->countMessagesWithMedia($messages)],
                [(string) __('exports.telegram.summary.messages_with_gifts'), $this->countMessagesWithGifts($messages)],
                [(string) __('exports.telegram.summary.total_message_reactions'), $this->countTotalReactions($messages)],
                [(string) __('exports.telegram.summary.total_comment_reactions'), $this->countTotalReactions($comments)],
                [(string) __('exports.telegram.summary.generated_at'), Carbon::now($this->config->timezone())->toDateTimeString()],
            ],
            columnWidths: [
                'A' => 24,
                'B' => 42,
            ],
            hyperlinkColumns: ['B'],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildMessagesSheet(array $payload): SheetDefinition
    {
        $messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
        $rows = [];

        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $rows[] = [
                (int) ($message['id'] ?? 0),
                $this->excelDate($message['date'] ?? null),
                (int) ($message['authorId'] ?? 0),
                (int) ($message['views'] ?? 0),
                (int) ($message['forwards'] ?? 0),
                (int) ($message['repliesCount'] ?? 0),
                $this->sumReactionCounts($message['reactions'] ?? []),
                $this->summarizeReactions($message['reactions'] ?? []),
                count($this->normalizeIntArray($message['reactionSenderIds'] ?? [])),
                !empty($message['gifts']['hasGift']) ? 'yes' : 'no',
                $this->summarizeGiftTypes($message['gifts'] ?? []),
                (string) (($message['media']['label'] ?? $message['media']['type'] ?? '') ?: ''),
                (string) ($message['message'] ?? ''),
                (string) ($message['telegramUrl'] ?? ''),
            ];
        }

        return new SheetDefinition(
            title: (string) __('exports.telegram.sheets.messages'),
            headings: $this->translations('exports.telegram.messages.headings'),
            rows: $rows,
            columnFormats: [
                'B' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
            columnWidths: [
                'A' => 12,
                'B' => 20,
                'C' => 14,
                'D' => 10,
                'E' => 10,
                'F' => 10,
                'G' => 12,
                'H' => 28,
                'I' => 14,
                'J' => 10,
                'K' => 18,
                'L' => 16,
                'M' => 64,
                'N' => 34,
            ],
            hyperlinkColumns: ['N'],
            centeredColumns: ['A', 'C', 'D', 'E', 'F', 'G', 'I', 'J'],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildCommentsSheet(array $payload): SheetDefinition
    {
        $comments = is_array($payload['commentsIndex'] ?? null) ? $payload['commentsIndex'] : [];
        $rows = [];

        foreach ($comments as $comment) {
            if (!is_array($comment)) {
                continue;
            }

            $rows[] = [
                (int) ($comment['postId'] ?? 0),
                (int) ($comment['id'] ?? 0),
                $this->excelDate($comment['date'] ?? null),
                (int) ($comment['authorId'] ?? 0),
                $this->sumReactionCounts($comment['reactions'] ?? []),
                $this->summarizeReactions($comment['reactions'] ?? []),
                (string) ($comment['message'] ?? ''),
            ];
        }

        return new SheetDefinition(
            title: (string) __('exports.telegram.sheets.comments'),
            headings: $this->translations('exports.telegram.comments.headings'),
            rows: $rows,
            columnFormats: [
                'C' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
            columnWidths: [
                'A' => 12,
                'B' => 12,
                'C' => 20,
                'D' => 14,
                'E' => 12,
                'F' => 28,
                'G' => 64,
            ],
            centeredColumns: ['A', 'B', 'D', 'E'],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildReactionsSheet(array $payload): SheetDefinition
    {
        $reactions = is_array($payload['reactionsIndex'] ?? null) ? $payload['reactionsIndex'] : [];
        $rows = [];

        foreach ($reactions as $reaction) {
            if (!is_array($reaction)) {
                continue;
            }

            $rows[] = [
                (string) ($reaction['entityType'] ?? ''),
                (int) ($reaction['messageId'] ?? 0),
                (int) ($reaction['commentId'] ?? 0),
                (string) ($reaction['reactionKey'] ?? ''),
                (string) ($reaction['reaction'] ?? ''),
                (int) ($reaction['count'] ?? 0),
                count($this->normalizeIntArray($reaction['senderIds'] ?? [])),
                implode(', ', $this->normalizeIntArray($reaction['senderIds'] ?? [])),
            ];
        }

        return new SheetDefinition(
            title: (string) __('exports.telegram.sheets.reactions'),
            headings: $this->translations('exports.telegram.reactions.headings'),
            rows: $rows,
            columnWidths: [
                'A' => 14,
                'B' => 12,
                'C' => 12,
                'D' => 24,
                'E' => 18,
                'F' => 10,
                'G' => 14,
                'H' => 34,
            ],
            centeredColumns: ['B', 'C', 'F', 'G'],
        );
    }

    /**
     * @param array<int, mixed> $messages
     */
    private function countMessagesWithMedia(array $messages): int
    {
        $count = 0;

        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            if (!empty($message['media']['hasMedia'])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<int, mixed> $messages
     */
    private function countMessagesWithGifts(array $messages): int
    {
        $count = 0;

        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            if (!empty($message['gifts']['hasGift'])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<int, mixed> $items
     */
    private function countTotalReactions(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $total += $this->sumReactionCounts($item['reactions'] ?? []);
        }

        return $total;
    }

    private function buildChatUrl(string $chatUsername): string
    {
        $chatUsername = ltrim(trim($chatUsername), '@');

        return $chatUsername !== '' ? sprintf('https://t.me/%s', $chatUsername) : '';
    }

    private function excelDate(mixed $value): mixed
    {
        if (!is_numeric($value)) {
            return null;
        }

        return ExcelDate::dateTimeToExcel(
            Carbon::createFromTimestamp((int) $value, $this->config->timezone())
        );
    }

    private function sumReactionCounts(mixed $value): int
    {
        if (!is_array($value)) {
            return 0;
        }

        $total = 0;

        foreach ($value as $reaction) {
            if (!is_array($reaction)) {
                continue;
            }

            $total += (int) ($reaction['count'] ?? 0);
        }

        return $total;
    }

    private function summarizeReactions(mixed $value): string
    {
        if (!is_array($value)) {
            return '';
        }

        $parts = [];

        foreach ($value as $reaction) {
            if (!is_array($reaction)) {
                continue;
            }

            $label = trim((string) ($reaction['emoji'] ?? $reaction['reaction'] ?? ''));
            $count = (int) ($reaction['count'] ?? 0);

            if ($label === '' && $count <= 0) {
                continue;
            }

            $parts[] = sprintf(
                '%s x%d',
                $label !== '' ? $label : (string) __('exports.common.reaction'),
                $count,
            );
        }

        return implode('; ', $parts);
    }

    private function summarizeGiftTypes(mixed $value): string
    {
        if (!is_array($value)) {
            return '';
        }

        $types = is_array($value['types'] ?? null) ? $value['types'] : [];

        return implode('; ', array_filter(array_map(
            static fn (mixed $type): string => trim((string) $type),
            $types,
        )));
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIntArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            $intValue = (int) $item;

            if ($intValue > 0) {
                $items[] = $intValue;
            }
        }

        return array_values(array_unique($items));
    }

    /**
     * @return array<int, string>
     */
    private function translations(string $key): array
    {
        $value = __($key);

        return is_array($value) ? array_values($value) : [];
    }
}
