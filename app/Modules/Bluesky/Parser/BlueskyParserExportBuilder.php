<?php

namespace App\Modules\Bluesky\Parser;

use App\Modules\Bluesky\Parser\Contracts\BlueskyParserExportBuilderInterface;
use App\Modules\Export\Excel\SheetDefinition;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

final class BlueskyParserExportBuilder implements BlueskyParserExportBuilderInterface
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
        $posts = is_array($payload['postsIndex'] ?? null) ? $payload['postsIndex'] : [];
        $authoredReplies = is_array($payload['authoredRepliesIndex'] ?? null) ? $payload['authoredRepliesIndex'] : [];
        $receivedReplies = is_array($payload['receivedRepliesIndex'] ?? null) ? $payload['receivedRepliesIndex'] : [];
        $reactions = is_array($payload['reactionsIndex'] ?? null) ? $payload['reactionsIndex'] : [];
        $authoredItems = [...$posts, ...$authoredReplies];

        return new SheetDefinition(
            title: (string) __('exports.bluesky.sheets.summary'),
            headings: $this->translations('exports.bluesky.summary.headings'),
            rows: [
                [(string) __('exports.bluesky.summary.source'), 'Bluesky'],
                [(string) __('exports.bluesky.summary.actor_query'), (string) ($payload['actor'] ?? '')],
                [(string) __('exports.bluesky.summary.resolved_handle'), (string) ($resolvedActor['handle'] ?? '')],
                [(string) __('exports.bluesky.summary.resolved_did'), (string) ($resolvedActor['did'] ?? '')],
                [(string) __('exports.bluesky.summary.display_name'), (string) ($resolvedActor['displayName'] ?? '')],
                [(string) __('exports.bluesky.summary.profile_url'), (string) ($resolvedActor['url'] ?? '')],
                [(string) __('exports.bluesky.summary.profile_followers'), (int) ($resolvedActor['followersCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.profile_follows'), (int) ($resolvedActor['followsCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.profile_posts'), (int) ($resolvedActor['postsCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.posts'), (int) ($payload['postsCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.authored_replies'), (int) ($payload['authoredRepliesCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.received_replies'), (int) ($payload['receivedRepliesCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.followers'), (int) ($payload['followersCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.follows'), (int) ($payload['followsCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.reactions'), (int) ($payload['reactionsCount'] ?? 0)],
                [(string) __('exports.bluesky.summary.posts_with_media'), $this->countBoolField($authoredItems, 'hasMedia')],
                [(string) __('exports.bluesky.summary.posts_with_links'), $this->countBoolField($authoredItems, 'hasLinks')],
                [(string) __('exports.bluesky.summary.unique_languages'), $this->countUniqueNestedValues($authoredItems, 'languages')],
                [(string) __('exports.bluesky.summary.total_replies'), $this->sumIntField([...$authoredItems, ...$receivedReplies], 'replyCount')],
                [(string) __('exports.bluesky.summary.total_reposts'), $this->sumIntField([...$authoredItems, ...$receivedReplies], 'repostCount')],
                [(string) __('exports.bluesky.summary.total_likes'), $this->sumIntField([...$authoredItems, ...$receivedReplies], 'likeCount')],
                [(string) __('exports.bluesky.summary.total_quotes'), $this->sumIntField([...$authoredItems, ...$receivedReplies], 'quoteCount')],
                [(string) __('exports.bluesky.summary.like_reactions'), $this->countKind($reactions, 'like')],
                [(string) __('exports.bluesky.summary.repost_reactions'), $this->countKind($reactions, 'repost')],
                [(string) __('exports.bluesky.summary.unique_reactors'), $this->countUniqueActorDid($reactions)],
                [(string) __('exports.bluesky.summary.generated_at'), Carbon::now($this->timezone())->toDateTimeString()],
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
            columnWidths: [
                'A' => 42,
                'B' => 26,
                'C' => 20,
                'D' => 14,
                'E' => 24,
                'F' => 24,
                'G' => 10,
                'H' => 10,
                'I' => 10,
                'J' => 10,
                'K' => 34,
                'L' => 72,
            ],
            hyperlinkColumns: ['K'],
            centeredColumns: ['C', 'D', 'G', 'H', 'I', 'J'],
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
            columnWidths: [
                'A' => 42,
                'B' => 42,
                'C' => 42,
                'D' => 20,
                'E' => 24,
                'F' => 24,
                'G' => 10,
                'H' => 10,
                'I' => 10,
                'J' => 10,
                'K' => 34,
                'L' => 72,
            ],
            hyperlinkColumns: ['K'],
            centeredColumns: ['D', 'G', 'H', 'I', 'J'],
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
            columnWidths: [
                'A' => 34,
                'B' => 24,
                'C' => 24,
                'D' => 34,
                'E' => 12,
                'F' => 12,
                'G' => 12,
                'H' => 72,
            ],
            hyperlinkColumns: ['D'],
            centeredColumns: ['E', 'F', 'G'],
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
            columnWidths: [
                'A' => 42,
                'B' => 26,
                'C' => 14,
                'D' => 34,
                'E' => 24,
                'F' => 24,
                'G' => 20,
                'H' => 20,
            ],
            centeredColumns: ['C', 'G', 'H'],
        );
    }

    private function excelDate(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
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
    private function countUniqueNestedValues(array $items, string $field): int
    {
        $values = [];

        foreach ($items as $item) {
            $nested = is_array($item[$field] ?? null) ? $item[$field] : [];

            foreach ($nested as $value) {
                $normalized = trim((string) $value);

                if ($normalized !== '') {
                    $values[$normalized] = true;
                }
            }
        }

        return count($values);
    }

    /**
     * @param array<int, array<string, mixed>> $reactions
     */
    private function countKind(array $reactions, string $kind): int
    {
        $count = 0;

        foreach ($reactions as $reaction) {
            if ((string) ($reaction['kind'] ?? '') === $kind) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<int, array<string, mixed>> $reactions
     */
    private function countUniqueActorDid(array $reactions): int
    {
        $actors = [];

        foreach ($reactions as $reaction) {
            $actor = is_array($reaction['actor'] ?? null) ? $reaction['actor'] : [];
            $did = trim((string) ($actor['did'] ?? ''));

            if ($did !== '') {
                $actors[$did] = true;
            }
        }

        return count($actors);
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
