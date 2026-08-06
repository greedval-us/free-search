<?php

namespace App\Modules\Bluesky\Parser;

use App\Modules\Bluesky\Parser\Contracts\BlueskyParserExportBuilderInterface;
use App\Modules\Export\Excel\SheetDefinition;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

final class BlueskyParserExportBuilder implements BlueskyParserExportBuilderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<int, SheetDefinition>
     */
    public function buildSheets(array $payload): array
    {
        return [
            $this->buildSummarySheet($payload),
            $this->buildPostsSheet($payload),
            $this->buildAuthoredRepliesSheet($payload),
            $this->buildReceivedRepliesSheet($payload),
            $this->buildActorsSheet($payload, 'followersIndex', (string) __('exports.bluesky.sheets.followers')),
            $this->buildActorsSheet($payload, 'followsIndex', (string) __('exports.bluesky.sheets.follows')),
            $this->buildReactionsSheet($payload),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildSummarySheet(array $payload): SheetDefinition
    {
        $resolvedActor = is_array($payload['resolvedActor'] ?? null) ? $payload['resolvedActor'] : [];

        return new SheetDefinition(
            title: (string) __('exports.bluesky.sheets.summary'),
            headings: $this->translations('exports.bluesky.summary.headings'),
            rows: [
                [(string) __('exports.bluesky.summary.source'), 'Bluesky'],
                [(string) __('exports.bluesky.summary.actor_query'), (string) ($payload['actor'] ?? '')],
                [(string) __('exports.bluesky.summary.resolved_handle'), (string) ($resolvedActor['handle'] ?? '')],
                [(string) __('exports.bluesky.summary.resolved_did'), (string) ($resolvedActor['did'] ?? '')],
                [(string) __('exports.bluesky.summary.display_name'), (string) ($resolvedActor['displayName'] ?? '')],
                [(string) __('exports.bluesky.summary.posts'), (int) ($payload['postsCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.authored_replies'), (int) ($payload['authoredRepliesCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.received_replies'), (int) ($payload['receivedRepliesCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.followers'), (int) ($payload['followersCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.follows'), (int) ($payload['followsCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.reactions'), (int) ($payload['reactionsCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.generated_at'), Carbon::now((string) config('app.timezone', 'UTC'))->toDateTimeString()],
            ],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildPostsSheet(array $payload): SheetDefinition
    {
        return $this->buildPostSheet(
            title: (string) __('exports.bluesky.sheets.posts'),
            items: is_array($payload['postsIndex'] ?? null) ? $payload['postsIndex'] : [],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildAuthoredRepliesSheet(array $payload): SheetDefinition
    {
        return $this->buildPostSheet(
            title: (string) __('exports.bluesky.sheets.authored_replies'),
            items: is_array($payload['authoredRepliesIndex'] ?? null) ? $payload['authoredRepliesIndex'] : [],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function buildPostSheet(string $title, array $items): SheetDefinition
    {
        $rows = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $author = is_array($item['author'] ?? null) ? $item['author'] : [];
            $rows[] = [
                (string) ($item['uri'] ?? ''),
                (string) ($item['cid'] ?? ''),
                $this->excelDate($item['createdAt'] ?? null),
                (string) ($item['postType'] ?? ''),
                (string) ($author['handle'] ?? ''),
                (string) ($author['displayName'] ?? ''),
                (int) ($item['replyCount'] ?? 0),
                (int) ($item['repostCount'] ?? 0),
                (int) ($item['likeCount'] ?? 0),
                (int) ($item['quoteCount'] ?? 0),
                (string) ($item['url'] ?? ''),
                (string) ($item['text'] ?? ''),
            ];
        }

        return new SheetDefinition(
            title: $title,
            headings: $this->translations('exports.bluesky.posts.headings'),
            rows: $rows,
            columnFormats: [
                'C' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildReceivedRepliesSheet(array $payload): SheetDefinition
    {
        $items = is_array($payload['receivedRepliesIndex'] ?? null) ? $payload['receivedRepliesIndex'] : [];
        $rows = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $author = is_array($item['author'] ?? null) ? $item['author'] : [];
            $rows[] = [
                (string) ($item['rootPostUri'] ?? ''),
                (string) ($item['uri'] ?? ''),
                (string) ($item['replyParentUri'] ?? ''),
                $this->excelDate($item['createdAt'] ?? null),
                (string) ($author['handle'] ?? ''),
                (string) ($author['displayName'] ?? ''),
                (int) ($item['replyCount'] ?? 0),
                (int) ($item['repostCount'] ?? 0),
                (int) ($item['likeCount'] ?? 0),
                (int) ($item['quoteCount'] ?? 0),
                (string) ($item['url'] ?? ''),
                (string) ($item['text'] ?? ''),
            ];
        }

        return new SheetDefinition(
            title: (string) __('exports.bluesky.sheets.received_replies'),
            headings: $this->translations('exports.bluesky.received_replies.headings'),
            rows: $rows,
            columnFormats: [
                'D' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildActorsSheet(array $payload, string $key, string $title): SheetDefinition
    {
        $items = is_array($payload[$key] ?? null) ? $payload[$key] : [];
        $rows = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rows[] = [
                (string) ($item['did'] ?? ''),
                (string) ($item['handle'] ?? ''),
                (string) ($item['displayName'] ?? ''),
                (string) ($item['url'] ?? ''),
                (int) ($item['followersCount'] ?? 0),
                (int) ($item['followsCount'] ?? 0),
                (int) ($item['postsCount'] ?? 0),
                (string) ($item['description'] ?? ''),
            ];
        }

        return new SheetDefinition(
            title: $title,
            headings: $this->translations('exports.bluesky.actors.headings'),
            rows: $rows,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildReactionsSheet(array $payload): SheetDefinition
    {
        $items = is_array($payload['reactionsIndex'] ?? null) ? $payload['reactionsIndex'] : [];
        $rows = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $actor = is_array($item['actor'] ?? null) ? $item['actor'] : [];
            $rows[] = [
                (string) ($item['postUri'] ?? ''),
                (string) ($item['postCid'] ?? ''),
                (string) ($item['kind'] ?? ''),
                (string) ($actor['did'] ?? ''),
                (string) ($actor['handle'] ?? ''),
                (string) ($actor['displayName'] ?? ''),
                $this->excelDate($item['createdAt'] ?? null),
                $this->excelDate($item['indexedAt'] ?? null),
            ];
        }

        return new SheetDefinition(
            title: (string) __('exports.bluesky.sheets.reactions'),
            headings: $this->translations('exports.bluesky.reactions.headings'),
            rows: $rows,
            columnFormats: [
                'G' => NumberFormat::FORMAT_DATE_DATETIME,
                'H' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
        );
    }

    private function excelDate(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return ExcelDate::dateTimeToExcel(
                Carbon::parse($value, (string) config('app.timezone', 'UTC'))
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
