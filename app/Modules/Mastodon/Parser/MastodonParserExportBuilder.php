<?php

namespace App\Modules\Mastodon\Parser;

use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserExportBuilderInterface;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

final class MastodonParserExportBuilder implements MastodonParserExportBuilderInterface
{
    private const TIMEZONE = 'UTC';

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
        $statuses = is_array($payload['statusesIndex'] ?? null) ? $payload['statusesIndex'] : [];
        $comments = is_array($payload['commentsIndex'] ?? null) ? $payload['commentsIndex'] : [];

        return new SheetDefinition(
            title: (string) __('exports.mastodon.sheets.summary'),
            headings: $this->translations('exports.mastodon.summary.headings'),
            rows: [
                [(string) __('exports.mastodon.summary.source'), 'Mastodon'],
                [(string) __('exports.mastodon.summary.account_query'), (string) ($payload['account'] ?? '')],
                [(string) __('exports.mastodon.summary.resolved_account'), (string) ($resolvedAccount['acct'] ?? '')],
                [(string) __('exports.mastodon.summary.display_name'), (string) ($resolvedAccount['displayName'] ?? '')],
                [(string) __('exports.mastodon.summary.account_url'), (string) ($resolvedAccount['url'] ?? '')],
                [(string) __('exports.mastodon.summary.instance_domain'), (string) ($resolvedAccount['instanceDomain'] ?? '')],
                [(string) __('exports.mastodon.summary.followers'), (int) ($resolvedAccount['followersCount'] ?? 0)],
                [(string) __('exports.mastodon.summary.following'), (int) ($resolvedAccount['followingCount'] ?? 0)],
                [(string) __('exports.mastodon.summary.profile_statuses'), (int) ($resolvedAccount['statusesCount'] ?? 0)],
                [(string) __('exports.mastodon.summary.statuses'), (int) ($payload['statusesCount'] ?? 0)],
                [(string) __('exports.mastodon.summary.comments'), (int) ($payload['commentsCount'] ?? 0)],
                [(string) __('exports.mastodon.summary.statuses_with_media'), $this->countBoolField($statuses, 'hasMedia')],
                [(string) __('exports.mastodon.summary.statuses_with_links'), $this->countBoolField($statuses, 'hasLinks')],
                [(string) __('exports.mastodon.summary.sensitive_statuses'), $this->countBoolField($statuses, 'sensitive')],
                [(string) __('exports.mastodon.summary.unique_languages'), $this->countUniqueScalar($statuses, 'language')],
                [(string) __('exports.mastodon.summary.total_status_replies'), $this->sumIntField($statuses, 'repliesCount')],
                [(string) __('exports.mastodon.summary.total_status_boosts'), $this->sumIntField($statuses, 'reblogsCount')],
                [(string) __('exports.mastodon.summary.total_status_favourites'), $this->sumIntField($statuses, 'favouritesCount')],
                [(string) __('exports.mastodon.summary.total_comment_replies'), $this->sumIntField($comments, 'repliesCount')],
                [(string) __('exports.mastodon.summary.total_comment_boosts'), $this->sumIntField($comments, 'reblogsCount')],
                [(string) __('exports.mastodon.summary.total_comment_favourites'), $this->sumIntField($comments, 'favouritesCount')],
                [(string) __('exports.mastodon.summary.generated_at'), Carbon::now($this->timezone())->toDateTimeString()],
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
            title: (string) __('exports.mastodon.sheets.statuses'),
            headings: $this->translations('exports.mastodon.statuses.headings'),
            rows: $rows,
            columnFormats: [
                'B' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
            columnWidths: [
                'A' => 18,
                'B' => 20,
                'C' => 14,
                'D' => 24,
                'E' => 24,
                'F' => 12,
                'G' => 14,
                'H' => 10,
                'I' => 10,
                'J' => 10,
                'K' => 34,
                'L' => 72,
            ],
            hyperlinkColumns: ['K'],
            centeredColumns: ['B', 'C', 'F', 'G', 'H', 'I', 'J'],
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
            title: (string) __('exports.mastodon.sheets.comments'),
            headings: $this->translations('exports.mastodon.comments.headings'),
            rows: $rows,
            columnFormats: [
                'D' => NumberFormat::FORMAT_DATE_DATETIME,
            ],
            columnWidths: [
                'A' => 18,
                'B' => 18,
                'C' => 18,
                'D' => 20,
                'E' => 14,
                'F' => 24,
                'G' => 24,
                'H' => 12,
                'I' => 10,
                'J' => 10,
                'K' => 10,
                'L' => 34,
                'M' => 72,
            ],
            hyperlinkColumns: ['L'],
            centeredColumns: ['D', 'E', 'H', 'I', 'J', 'K'],
        );
    }

    private function excelDate(mixed $value): mixed
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return ExcelDate::dateTimeToExcel(
                Carbon::parse($value, $this->timezone())
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function timezone(): string
    {
        $timezone = (string) config('app.timezone', self::TIMEZONE);

        return trim($timezone) !== '' ? $timezone : self::TIMEZONE;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function countBoolField(array $items, string $field): int
    {
        $count = 0;

        foreach ($items as $item) {
            if (!empty($item[$field])) {
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
     * @param array<int, array<string, mixed>> $items
     */
    private function countUniqueScalar(array $items, string $field): int
    {
        $values = [];

        foreach ($items as $item) {
            $value = trim((string) ($item[$field] ?? ''));

            if ($value !== '') {
                $values[$value] = true;
            }
        }

        return count($values);
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
