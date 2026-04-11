<?php

namespace App\Modules\Telegram\Parser;

use App\Modules\Export\Excel\SheetDefinition;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TelegramParserExportBuilder
{
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

        return new SheetDefinition(
            title: 'Summary',
            headings: ['Field', 'Value'],
            rows: [
                ['Source', 'Telegram'],
                ['Channel', (string) ($payload['chatUsername'] ?? '')],
                ['Period', (string) ($payload['period'] ?? '')],
                ['Keyword', (string) ($payload['keyword'] ?? '')],
                ['Date from', (string) ($range['dateFrom'] ?? '')],
                ['Date to', (string) ($range['dateTo'] ?? '')],
                ['Is channel', (bool) ($payload['isChannel'] ?? false) ? 'yes' : 'no'],
                ['Messages', (int) ($payload['messagesCount'] ?? 0)],
                ['Comments', (int) ($payload['commentsCount'] ?? 0)],
                ['Generated at', Carbon::now(config('app.timezone'))->toDateTimeString()],
            ],
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
                (string) ($message['message'] ?? ''),
                (int) ($message['views'] ?? 0),
                (int) ($message['forwards'] ?? 0),
                (int) ($message['repliesCount'] ?? 0),
                (string) (($message['media']['type'] ?? '') ?: ''),
                (string) ($message['telegramUrl'] ?? ''),
                $this->stringify($message['reactions'] ?? []),
                $this->stringify($message['gifts'] ?? []),
            ];
        }

        return new SheetDefinition(
            title: 'Messages',
            headings: [
                'Message ID',
                'Date',
                'Author ID',
                'Text',
                'Views',
                'Forwards',
                'Replies',
                'Media type',
                'Telegram URL',
                'Reactions',
                'Gifts',
            ],
            rows: $rows,
            columnFormats: [
                'B' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
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
                (string) ($comment['message'] ?? ''),
                $this->stringify($comment['reactions'] ?? []),
            ];
        }

        return new SheetDefinition(
            title: 'Comments',
            headings: [
                'Post ID',
                'Comment ID',
                'Date',
                'Author ID',
                'Text',
                'Reactions',
            ],
            rows: $rows,
            columnFormats: [
                'C' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
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
                implode(',', array_map('intval', is_array($reaction['senderIds'] ?? null) ? $reaction['senderIds'] : [])),
            ];
        }

        return new SheetDefinition(
            title: 'Reactions',
            headings: [
                'Entity type',
                'Message ID',
                'Comment ID',
                'Reaction key',
                'Reaction',
                'Count',
                'Sender IDs',
            ],
            rows: $rows,
        );
    }

    private function excelDate(mixed $value): mixed
    {
        if (!is_numeric($value)) {
            return null;
        }

        return ExcelDate::dateTimeToExcel(
            Carbon::createFromTimestamp((int) $value, config('app.timezone'))
        );
    }

    private function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return '';
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }
}

