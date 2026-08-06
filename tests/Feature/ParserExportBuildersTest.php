<?php

namespace Tests\Feature;

use App\Modules\Bluesky\Parser\BlueskyParserExportBuilder;
use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\Mastodon\Parser\MastodonParserExportBuilder;
use App\Modules\YouTube\Parser\YouTubeParserExportBuilder;
use App\Modules\YouTube\Support\YouTubeModuleConfig;
use Tests\TestCase;

class ParserExportBuildersTest extends TestCase
{
    public function test_youtube_export_builder_produces_analytics_summary_and_sheet_formatting(): void
    {
        app()->setLocale('en');

        $builder = new YouTubeParserExportBuilder(
            YouTubeModuleConfig::fromArray([], 'UTC')
        );

        $sheets = $builder->buildSheets([
            'videoId' => 'abc123',
            'commentsCount' => 2,
            'repliesCount' => 2,
            'commentsIndex' => [
                [
                    'commentId' => 'c1',
                    'threadId' => 't1',
                    'videoId' => 'abc123',
                    'author' => 'Alice',
                    'authorChannelUrl' => 'https://youtube.com/@alice',
                    'text' => 'First comment',
                    'likeCount' => 5,
                    'publishedAt' => '2026-08-01T10:00:00Z',
                    'replyCount' => 2,
                ],
                [
                    'commentId' => 'c2',
                    'threadId' => 't2',
                    'videoId' => 'abc123',
                    'author' => 'Bob',
                    'authorChannelUrl' => 'https://youtube.com/@bob',
                    'text' => 'Second comment',
                    'likeCount' => 3,
                    'publishedAt' => '2026-08-03T15:30:00Z',
                    'replyCount' => 0,
                ],
            ],
            'repliesIndex' => [
                [
                    'replyId' => 'r1',
                    'parentCommentId' => 'c1',
                    'threadId' => 't1',
                    'author' => 'Alice',
                    'authorChannelUrl' => 'https://youtube.com/@alice',
                    'text' => 'Reply one',
                    'likeCount' => 2,
                    'publishedAt' => '2026-08-02T09:00:00Z',
                ],
                [
                    'replyId' => 'r2',
                    'parentCommentId' => 'c1',
                    'threadId' => 't1',
                    'author' => 'Carol',
                    'authorChannelUrl' => 'https://youtube.com/@carol',
                    'text' => 'Reply two',
                    'likeCount' => 4,
                    'publishedAt' => '2026-08-04T18:00:00Z',
                ],
            ],
        ]);

        $this->assertCount(3, $sheets);

        $summary = $this->rowsByField($sheets[0]);
        $this->assertSame('YouTube', $summary['Source']);
        $this->assertSame('abc123', $summary['Video ID']);
        $this->assertSame('https://www.youtube.com/watch?v=abc123', $summary['Video URL']);
        $this->assertSame(3, $summary['Unique authors']);
        $this->assertSame(1, $summary['Comments with replies']);
        $this->assertSame(8, $summary['Total comment likes']);
        $this->assertSame(6, $summary['Total reply likes']);
        $this->assertSame('2026-08-01 10:00:00', $summary['First published at']);
        $this->assertSame('2026-08-04 18:00:00', $summary['Last published at']);
        $this->assertSame(['B'], $sheets[0]->hyperlinkColumns);

        $commentsSheet = $sheets[1];
        $this->assertSame(['F'], $commentsSheet->hyperlinkColumns);
        $this->assertSame(['A', 'B', 'C', 'D', 'H', 'I'], $commentsSheet->centeredColumns);
        $this->assertSame(72, $commentsSheet->columnWidths['G']);
    }

    public function test_mastodon_export_builder_includes_profile_and_engagement_summary_metrics(): void
    {
        app()->setLocale('en');

        $builder = new MastodonParserExportBuilder();

        $sheets = $builder->buildSheets([
            'account' => '@alice@example.social',
            'statusesCount' => 2,
            'commentsCount' => 1,
            'resolvedAccount' => [
                'acct' => 'alice@example.social',
                'displayName' => 'Alice',
                'url' => 'https://example.social/@alice',
                'instanceDomain' => 'example.social',
                'followersCount' => 120,
                'followingCount' => 45,
                'statusesCount' => 999,
            ],
            'statusesIndex' => [
                [
                    'id' => 's1',
                    'createdAt' => '2026-08-01T10:00:00Z',
                    'postType' => 'original',
                    'language' => 'en',
                    'visibility' => 'public',
                    'repliesCount' => 4,
                    'reblogsCount' => 6,
                    'favouritesCount' => 10,
                    'hasMedia' => true,
                    'hasLinks' => true,
                    'sensitive' => false,
                    'url' => 'https://example.social/@alice/s1',
                    'content' => 'Status one',
                    'account' => [
                        'acct' => 'alice@example.social',
                        'displayName' => 'Alice',
                    ],
                ],
                [
                    'id' => 's2',
                    'createdAt' => '2026-08-02T10:00:00Z',
                    'postType' => 'reply',
                    'language' => 'ru',
                    'visibility' => 'unlisted',
                    'repliesCount' => 1,
                    'reblogsCount' => 2,
                    'favouritesCount' => 3,
                    'hasMedia' => false,
                    'hasLinks' => false,
                    'sensitive' => true,
                    'url' => 'https://example.social/@alice/s2',
                    'content' => 'Status two',
                    'account' => [
                        'acct' => 'alice@example.social',
                        'displayName' => 'Alice',
                    ],
                ],
            ],
            'commentsIndex' => [
                [
                    'rootStatusId' => 's1',
                    'commentId' => 'c1',
                    'parentStatusId' => 's1',
                    'createdAt' => '2026-08-03T10:00:00Z',
                    'postType' => 'reply',
                    'language' => 'en',
                    'repliesCount' => 2,
                    'reblogsCount' => 1,
                    'favouritesCount' => 5,
                    'url' => 'https://example.social/@alice/c1',
                    'content' => 'Comment one',
                    'account' => [
                        'acct' => 'bob@example.social',
                        'displayName' => 'Bob',
                    ],
                ],
            ],
        ]);

        $this->assertCount(3, $sheets);

        $summary = $this->rowsByField($sheets[0]);
        $this->assertSame('https://example.social/@alice', $summary['Account URL']);
        $this->assertSame('example.social', $summary['Instance domain']);
        $this->assertSame(120, $summary['Followers']);
        $this->assertSame(45, $summary['Following']);
        $this->assertSame(999, $summary['Profile statuses']);
        $this->assertSame(1, $summary['Statuses with media']);
        $this->assertSame(1, $summary['Statuses with links']);
        $this->assertSame(1, $summary['Sensitive statuses']);
        $this->assertSame(2, $summary['Unique languages']);
        $this->assertSame(5, $summary['Total status replies']);
        $this->assertSame(8, $summary['Total status boosts']);
        $this->assertSame(13, $summary['Total status favourites']);
        $this->assertSame(2, $summary['Total comment replies']);
        $this->assertSame(1, $summary['Total comment boosts']);
        $this->assertSame(5, $summary['Total comment favourites']);
        $this->assertSame(['B'], $sheets[0]->hyperlinkColumns);

        $statusesSheet = $sheets[1];
        $this->assertSame(['K'], $statusesSheet->hyperlinkColumns);
        $this->assertSame(['B', 'C', 'F', 'G', 'H', 'I', 'J'], $statusesSheet->centeredColumns);
        $this->assertSame(72, $statusesSheet->columnWidths['L']);
    }

    public function test_bluesky_export_builder_includes_profile_content_and_reaction_aggregates(): void
    {
        app()->setLocale('en');

        $builder = new BlueskyParserExportBuilder();

        $sheets = $builder->buildSheets([
            'actor' => '@alice.bsky.social',
            'postsCount' => 1,
            'authoredRepliesCount' => 1,
            'receivedRepliesCount' => 1,
            'followersCount' => 1,
            'followsCount' => 1,
            'reactionsCount' => 3,
            'resolvedActor' => [
                'handle' => 'alice.bsky.social',
                'did' => 'did:plc:alice',
                'displayName' => 'Alice',
                'url' => 'https://bsky.app/profile/alice.bsky.social',
                'followersCount' => 321,
                'followsCount' => 123,
                'postsCount' => 500,
            ],
            'postsIndex' => [
                [
                    'uri' => 'at://did:plc:alice/app.bsky.feed.post/1',
                    'cid' => 'cid-1',
                    'createdAt' => '2026-08-01T10:00:00Z',
                    'postType' => 'post',
                    'replyCount' => 2,
                    'repostCount' => 3,
                    'likeCount' => 4,
                    'quoteCount' => 1,
                    'url' => 'https://bsky.app/profile/alice.bsky.social/post/1',
                    'text' => 'Post one',
                    'hasMedia' => true,
                    'hasLinks' => true,
                    'languages' => ['en'],
                    'author' => [
                        'handle' => 'alice.bsky.social',
                        'displayName' => 'Alice',
                    ],
                ],
            ],
            'authoredRepliesIndex' => [
                [
                    'uri' => 'at://did:plc:alice/app.bsky.feed.post/2',
                    'cid' => 'cid-2',
                    'createdAt' => '2026-08-02T10:00:00Z',
                    'postType' => 'reply',
                    'replyCount' => 1,
                    'repostCount' => 0,
                    'likeCount' => 2,
                    'quoteCount' => 0,
                    'url' => 'https://bsky.app/profile/alice.bsky.social/post/2',
                    'text' => 'Reply one',
                    'hasMedia' => false,
                    'hasLinks' => false,
                    'languages' => ['ru'],
                    'author' => [
                        'handle' => 'alice.bsky.social',
                        'displayName' => 'Alice',
                    ],
                ],
            ],
            'receivedRepliesIndex' => [
                [
                    'rootPostUri' => 'at://did:plc:alice/app.bsky.feed.post/1',
                    'uri' => 'at://did:plc:bob/app.bsky.feed.post/3',
                    'replyParentUri' => 'at://did:plc:alice/app.bsky.feed.post/1',
                    'createdAt' => '2026-08-03T10:00:00Z',
                    'replyCount' => 0,
                    'repostCount' => 1,
                    'likeCount' => 5,
                    'quoteCount' => 1,
                    'url' => 'https://bsky.app/profile/bob.bsky.social/post/3',
                    'text' => 'Incoming reply',
                    'author' => [
                        'handle' => 'bob.bsky.social',
                        'displayName' => 'Bob',
                    ],
                ],
            ],
            'followersIndex' => [
                [
                    'did' => 'did:plc:follower',
                    'handle' => 'follower.bsky.social',
                    'displayName' => 'Follower',
                    'url' => 'https://bsky.app/profile/follower.bsky.social',
                    'followersCount' => 10,
                    'followsCount' => 5,
                    'postsCount' => 3,
                    'description' => 'Follower bio',
                ],
            ],
            'followsIndex' => [
                [
                    'did' => 'did:plc:followed',
                    'handle' => 'followed.bsky.social',
                    'displayName' => 'Followed',
                    'url' => 'https://bsky.app/profile/followed.bsky.social',
                    'followersCount' => 20,
                    'followsCount' => 7,
                    'postsCount' => 8,
                    'description' => 'Followed bio',
                ],
            ],
            'reactionsIndex' => [
                [
                    'postUri' => 'at://did:plc:alice/app.bsky.feed.post/1',
                    'postCid' => 'cid-1',
                    'kind' => 'like',
                    'actor' => ['did' => 'did:plc:one', 'handle' => 'one.bsky.social', 'displayName' => 'One'],
                    'createdAt' => '2026-08-04T10:00:00Z',
                    'indexedAt' => '2026-08-04T10:01:00Z',
                ],
                [
                    'postUri' => 'at://did:plc:alice/app.bsky.feed.post/1',
                    'postCid' => 'cid-1',
                    'kind' => 'like',
                    'actor' => ['did' => 'did:plc:two', 'handle' => 'two.bsky.social', 'displayName' => 'Two'],
                    'createdAt' => '2026-08-04T10:02:00Z',
                    'indexedAt' => '2026-08-04T10:03:00Z',
                ],
                [
                    'postUri' => 'at://did:plc:alice/app.bsky.feed.post/1',
                    'postCid' => 'cid-1',
                    'kind' => 'repost',
                    'actor' => ['did' => 'did:plc:one', 'handle' => 'one.bsky.social', 'displayName' => 'One'],
                    'createdAt' => '2026-08-04T10:04:00Z',
                    'indexedAt' => '2026-08-04T10:05:00Z',
                ],
            ],
        ]);

        $this->assertCount(7, $sheets);

        $summary = $this->rowsByField($sheets[0]);
        $this->assertSame('https://bsky.app/profile/alice.bsky.social', $summary['Profile URL']);
        $this->assertSame(321, $summary['Profile followers']);
        $this->assertSame(123, $summary['Profile follows']);
        $this->assertSame(500, $summary['Profile posts']);
        $this->assertSame(1, $summary['Posts with media']);
        $this->assertSame(1, $summary['Posts with links']);
        $this->assertSame(2, $summary['Unique languages']);
        $this->assertSame(3, $summary['Total replies']);
        $this->assertSame(4, $summary['Total reposts']);
        $this->assertSame(11, $summary['Total likes']);
        $this->assertSame(2, $summary['Total quotes']);
        $this->assertSame(2, $summary['Like reactions']);
        $this->assertSame(1, $summary['Repost reactions']);
        $this->assertSame(2, $summary['Unique reactors']);
        $this->assertSame(['B'], $sheets[0]->hyperlinkColumns);

        $postsSheet = $sheets[1];
        $this->assertSame(['K'], $postsSheet->hyperlinkColumns);
        $this->assertSame(['C', 'D', 'G', 'H', 'I', 'J'], $postsSheet->centeredColumns);
        $this->assertSame(72, $postsSheet->columnWidths['L']);
    }

    /**
     * @return array<string, mixed>
     */
    private function rowsByField(SheetDefinition $sheet): array
    {
        $rows = [];

        foreach ($sheet->rows as $row) {
            $field = (string) ($row[0] ?? '');

            if ($field === '') {
                continue;
            }

            $rows[$field] = $row[1] ?? null;
        }

        return $rows;
    }
}
