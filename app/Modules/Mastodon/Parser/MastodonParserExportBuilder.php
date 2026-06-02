<?php

namespace App\Modules\Mastodon\Parser;

use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserExportBuilderInterface;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

final class MastodonParserExportBuilder implements MastodonParserExportBuilderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<int, SheetDefinition>
     */
    public function buildSheets(array $payload): array
    {
        return [
            $this->buildSummarySheet($payload),
            $this->buildStatusesSheet($payload),
            $this->buildCommentsSheet($payload),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildSummarySheet(array $payload): SheetDefinition
    {
        $resolvedAccount = is_array($payload['resolvedAccount'] ?? null) ? $payload['resolvedAccount'] : [];

        return new SheetDefinition(
            title: 'Summary',
            headings: ['Field', 'Value'],
            rows: [
                ['Source', 'Mastodon'],
                ['Account query', (string) ($payload['account'] ?? '')],
                ['Resolved account', (string) ($resolvedAccount['acct'] ?? '')],
                ['Display name', (string) ($resolvedAccount['displayName'] ?? '')],
                ['Statuses', (int) ($payload['statusesCount'] ?? 0)],
                ['Comments', (int) ($payload['commentsCount'] ?? 0)],
                ['Generated at', Carbon::now((string) config('app.timezone', 'UTC'))->toDateTimeString()],
            ],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildStatusesSheet(array $payload): SheetDefinition
    {
        $statuses = is_array($payload['statusesIndex'] ?? null) ? $payload['statusesIndex'] : [];
        $rows = [];

        foreach ($statuses as $status) {
            if (!is_array($status)) {
                continue;
            }

            $account = is_array($status['account'] ?? null) ? $status['account'] : [];
            $rows[] = [
                (string) ($status['id'] ?? ''),
                $this->excelDate($status['createdAt'] ?? null),
                (string) ($status['postType'] ?? ''),
                (string) ($account['acct'] ?? ''),
                (string) ($account['displayName'] ?? ''),
                (string) ($status['language'] ?? ''),
                (string) ($status['visibility'] ?? ''),
                (int) ($status['repliesCount'] ?? 0),
                (int) ($status['reblogsCount'] ?? 0),
                (int) ($status['favouritesCount'] ?? 0),
                (string) ($status['url'] ?? ''),
                (string) ($status['content'] ?? ''),
            ];
        }

        return new SheetDefinition(
            title: 'Statuses',
            headings: [
                'Status ID',
                'Created at',
                'Post type',
                'Account',
                'Display name',
                'Language',
                'Visibility',
                'Replies',
                'Boosts',
                'Favorites',
                'URL',
                'Content',
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

            $account = is_array($comment['account'] ?? null) ? $comment['account'] : [];
            $rows[] = [
                (string) ($comment['rootStatusId'] ?? ''),
                (string) ($comment['commentId'] ?? ''),
                (string) ($comment['parentStatusId'] ?? ''),
                $this->excelDate($comment['createdAt'] ?? null),
                (string) ($comment['postType'] ?? ''),
                (string) ($account['acct'] ?? ''),
                (string) ($account['displayName'] ?? ''),
                (string) ($comment['language'] ?? ''),
                (int) ($comment['repliesCount'] ?? 0),
                (int) ($comment['reblogsCount'] ?? 0),
                (int) ($comment['favouritesCount'] ?? 0),
                (string) ($comment['url'] ?? ''),
                (string) ($comment['content'] ?? ''),
            ];
        }

        return new SheetDefinition(
            title: 'Comments',
            headings: [
                'Root status ID',
                'Comment ID',
                'Parent status ID',
                'Created at',
                'Post type',
                'Account',
                'Display name',
                'Language',
                'Replies',
                'Boosts',
                'Favorites',
                'URL',
                'Content',
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
                Carbon::parse($value, (string) config('app.timezone', 'UTC'))
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
