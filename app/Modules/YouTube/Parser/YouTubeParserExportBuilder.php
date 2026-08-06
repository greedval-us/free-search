<?php

namespace App\Modules\YouTube\Parser;

use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserExportBuilderInterface;
use App\Modules\YouTube\Support\YouTubeModuleConfig;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class YouTubeParserExportBuilder implements YouTubeParserExportBuilderInterface
{
    public function __construct(private readonly YouTubeModuleConfig $config)
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
            title: (string) __('exports.youtube.sheets.summary'),
            headings: $this->translations('exports.youtube.summary.headings'),
            rows: [
                [(string) __('exports.youtube.summary.source'), 'YouTube'],
                [(string) __('exports.youtube.summary.video_id'), (string) ($payload['videoId'] ?? '')],
                [(string) __('exports.youtube.summary.comments'), (int) ($payload['commentsCount'] ?? 0)],
                [(string) __('exports.youtube.summary.replies'), (int) ($payload['repliesCount'] ?? 0)],
                [(string) __('exports.youtube.summary.generated_at'), Carbon::now($this->config->timezone())->toDateTimeString()],
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
            title: (string) __('exports.youtube.sheets.comments'),
            headings: $this->translations('exports.youtube.comments.headings'),
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
            title: (string) __('exports.youtube.sheets.replies'),
            headings: $this->translations('exports.youtube.replies.headings'),
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
                Carbon::parse($value, $this->config->timezone())
            );
        } catch (\Throwable) {
            return null;
        }
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
