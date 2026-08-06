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
        $comments = is_array($payload['commentsIndex'] ?? null) ? $payload['commentsIndex'] : [];
        $replies = is_array($payload['repliesIndex'] ?? null) ? $payload['repliesIndex'] : [];
        $videoId = (string) ($payload['videoId'] ?? '');

        return new SheetDefinition(
            title: (string) __('exports.youtube.sheets.summary'),
            headings: $this->translations('exports.youtube.summary.headings'),
            rows: [
                [(string) __('exports.youtube.summary.source'), 'YouTube'],
                [(string) __('exports.youtube.summary.video_id'), $videoId],
                [(string) __('exports.youtube.summary.video_url'), $this->videoUrl($videoId)],
                [(string) __('exports.youtube.summary.comments'), (int) ($payload['commentsCount'] ?? 0)],
                [(string) __('exports.youtube.summary.replies'), (int) ($payload['repliesCount'] ?? 0)],
                [(string) __('exports.youtube.summary.unique_authors'), $this->countUniqueAuthors($comments, $replies)],
                [(string) __('exports.youtube.summary.comments_with_replies'), $this->countCommentsWithReplies($comments)],
                [(string) __('exports.youtube.summary.total_comment_likes'), $this->sumIntField($comments, 'likeCount')],
                [(string) __('exports.youtube.summary.total_reply_likes'), $this->sumIntField($replies, 'likeCount')],
                [(string) __('exports.youtube.summary.first_published_at'), $this->firstPublishedAt($comments, $replies)],
                [(string) __('exports.youtube.summary.last_published_at'), $this->lastPublishedAt($comments, $replies)],
                [(string) __('exports.youtube.summary.generated_at'), Carbon::now($this->config->timezone())->toDateTimeString()],
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
            columnWidths: [
                'A' => 24,
                'B' => 24,
                'C' => 20,
                'D' => 20,
                'E' => 22,
                'F' => 34,
                'G' => 72,
                'H' => 10,
                'I' => 10,
            ],
            hyperlinkColumns: ['F'],
            centeredColumns: ['A', 'B', 'C', 'D', 'H', 'I'],
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
            columnWidths: [
                'A' => 24,
                'B' => 24,
                'C' => 24,
                'D' => 20,
                'E' => 22,
                'F' => 34,
                'G' => 72,
                'H' => 10,
            ],
            hyperlinkColumns: ['F'],
            centeredColumns: ['A', 'B', 'C', 'D', 'H'],
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
     * @param array<int, array<string, mixed>> $comments
     * @param array<int, array<string, mixed>> $replies
     */
    private function countUniqueAuthors(array $comments, array $replies): int
    {
        $authors = [];

        foreach ([...$comments, ...$replies] as $item) {
            $key = trim((string) ($item['authorChannelUrl'] ?? $item['author'] ?? ''));

            if ($key !== '') {
                $authors[$key] = true;
            }
        }

        return count($authors);
    }

    /**
     * @param array<int, array<string, mixed>> $comments
     */
    private function countCommentsWithReplies(array $comments): int
    {
        $count = 0;

        foreach ($comments as $comment) {
            if ((int) ($comment['replyCount'] ?? 0) > 0) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function sumIntField(array $items, string $field): int
    {
        $total = 0;

        foreach ($items as $item) {
            $total += (int) ($item[$field] ?? 0);
        }

        return $total;
    }

    /**
     * @param array<int, array<string, mixed>> $comments
     * @param array<int, array<string, mixed>> $replies
     */
    private function firstPublishedAt(array $comments, array $replies): string
    {
        return $this->publishedAtBoundary([...$comments, ...$replies], true);
    }

    /**
     * @param array<int, array<string, mixed>> $comments
     * @param array<int, array<string, mixed>> $replies
     */
    private function lastPublishedAt(array $comments, array $replies): string
    {
        return $this->publishedAtBoundary([...$comments, ...$replies], false);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function publishedAtBoundary(array $items, bool $first): string
    {
        $timestamps = [];

        foreach ($items as $item) {
            $value = trim((string) ($item['publishedAt'] ?? ''));

            if ($value === '') {
                continue;
            }

            try {
                $timestamps[] = Carbon::parse($value, $this->config->timezone());
            } catch (\Throwable) {
            }
        }

        if ($timestamps === []) {
            return '';
        }

        usort(
            $timestamps,
            static fn (Carbon $left, Carbon $right): int => $left->getTimestamp() <=> $right->getTimestamp()
        );

        return ($first ? reset($timestamps) : end($timestamps))?->toDateTimeString() ?? '';
    }

    private function videoUrl(string $videoId): string
    {
        return $videoId !== '' ? sprintf('https://www.youtube.com/watch?v=%s', $videoId) : '';
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
