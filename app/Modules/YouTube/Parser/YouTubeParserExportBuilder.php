<?php

namespace App\Modules\YouTube\Parser;

use App\Modules\Export\Excel\SheetDefinition;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class YouTubeParserExportBuilder
{
    /**
     * @param array<string, mixed> $payload
     * @return array<int, SheetDefinition>
     */
    public function buildSheets(array $payload): array
    {
        return [
            $this->buildSummarySheet($payload),
            $this->buildCommentsSheet($payload),
            $this->buildRepliesSheet($payload),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildSummarySheet(array $payload): SheetDefinition
    {
        return new SheetDefinition(
            title: 'Summary',
            headings: ['Field', 'Value'],
            rows: [
                ['Source', 'YouTube'],
                ['Video ID', (string) ($payload['videoId'] ?? '')],
                ['Comments', (int) ($payload['commentsCount'] ?? 0)],
                ['Replies', (int) ($payload['repliesCount'] ?? 0)],
                ['Generated at', Carbon::now(config('app.timezone'))->toDateTimeString()],
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
                (string) ($comment['commentId'] ?? ''),
                (string) ($comment['threadId'] ?? ''),
                (string) ($comment['videoId'] ?? ''),
                $this->excelDate($comment['publishedAt'] ?? null),
                (string) ($comment['author'] ?? ''),
                (string) ($comment['authorChannelUrl'] ?? ''),
                (string) ($comment['text'] ?? ''),
                (int) ($comment['likeCount'] ?? 0),
                (int) ($comment['replyCount'] ?? 0),
            ];
        }

        return new SheetDefinition(
            title: 'Comments',
            headings: [
                'Comment ID',
                'Thread ID',
                'Video ID',
                'Published at',
                'Author',
                'Author channel URL',
                'Text',
                'Likes',
                'Reply count',
            ],
            rows: $rows,
            columnFormats: [
                'D' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildRepliesSheet(array $payload): SheetDefinition
    {
        $replies = is_array($payload['repliesIndex'] ?? null) ? $payload['repliesIndex'] : [];
        $rows = [];

        foreach ($replies as $reply) {
            if (!is_array($reply)) {
                continue;
            }

            $rows[] = [
                (string) ($reply['replyId'] ?? ''),
                (string) ($reply['parentCommentId'] ?? ''),
                (string) ($reply['threadId'] ?? ''),
                $this->excelDate($reply['publishedAt'] ?? null),
                (string) ($reply['author'] ?? ''),
                (string) ($reply['authorChannelUrl'] ?? ''),
                (string) ($reply['text'] ?? ''),
                (int) ($reply['likeCount'] ?? 0),
            ];
        }

        return new SheetDefinition(
            title: 'Replies',
            headings: [
                'Reply ID',
                'Parent comment ID',
                'Thread ID',
                'Published at',
                'Author',
                'Author channel URL',
                'Text',
                'Likes',
            ],
            rows: $rows,
            columnFormats: [
                'D' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
        );
    }

    private function excelDate(mixed $value): mixed
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return ExcelDate::dateTimeToExcel(
                Carbon::parse($value, config('app.timezone'))
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
