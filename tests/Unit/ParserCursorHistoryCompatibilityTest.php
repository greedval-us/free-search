<?php

namespace Tests\Unit;

use App\Modules\Bluesky\DTO\Parser\BlueskyParserCursorDTO;
use App\Modules\Mastodon\DTO\Parser\MastodonParserCursorDTO;
use App\Modules\YouTube\DTO\Parser\YouTubeParserCursorDTO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ParserCursorHistoryCompatibilityTest extends TestCase
{
    public function test_youtube_checkpoint_keeps_legacy_history_lists_and_detects_a_cycle_after_reload(): void
    {
        $checkpoint = [
            'commentsPageToken' => 'page-b',
            'commentsPage' => 2,
            'commentsTotalHint' => 200,
            'replyThreadIds' => ['thread-one', 'thread-two'],
            'replyThreadIndex' => 1,
            'replyPageToken' => 'reply-b',
            'nextAdvanceAt' => 50,
            'commentsPageTokens' => ['page-a', 'page-b'],
            'replyPageTokens' => ['reply-a', 'reply-b'],
        ];
        $cursor = YouTubeParserCursorDTO::fromArray($this->reload($checkpoint));
        $this->assertSame($checkpoint, $cursor->toArray());

        try {
            $cursor->setCommentsPageToken('page-a');
            $this->fail('A persisted earlier comments token must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('YouTube comments pagination cursor did not advance.', $exception->getMessage());
        }

        $this->assertSame($checkpoint, $cursor->toArray());
    }

    public function test_youtube_reply_history_resets_between_threads_and_comments_history_remains(): void
    {
        $cursor = new YouTubeParserCursorDTO;
        $cursor->setCommentsPageToken('same-token');
        $cursor->setReplyPageToken('same-token');
        $cursor = YouTubeParserCursorDTO::fromArray($this->reload($cursor->toArray()));
        try {
            $cursor->setReplyPageToken('same-token');
            $this->fail('The same reply page must not be processed twice in one thread.');
        } catch (RuntimeException $exception) {
            $this->assertSame('YouTube replies pagination cursor did not advance.', $exception->getMessage());
        }

        $cursor->setReplyPageToken(null);
        $cursor->setReplyPageToken('same-token');

        $this->assertSame(['same-token'], $cursor->toArray()['commentsPageTokens']);
        $this->assertSame(['same-token'], $cursor->toArray()['replyPageTokens']);
    }

    public function test_mastodon_checkpoint_keeps_legacy_history_and_detects_a_cycle_after_reload(): void
    {
        $checkpoint = [
            'statusesMaxId' => '200',
            'statusesPage' => 2,
            'statusesTotalHint' => 300,
            'commentStatusIds' => ['400', '300', '200'],
            'commentStatusIndex' => 0,
            'nextAdvanceAt' => 50,
            'statusesSeenMaxIds' => ['400', '300'],
        ];
        $cursor = MastodonParserCursorDTO::fromArray($this->reload($checkpoint));
        $this->assertSame($checkpoint, $cursor->toArray());
        try {
            $cursor->rememberPage($cursor->statusesMaxId(), '400');
            $this->fail('A persisted earlier statuses cursor must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Mastodon statuses pagination did not advance.', $exception->getMessage());
        }
        $this->assertSame($checkpoint, $cursor->toArray());

        $cursor->rememberPage($cursor->statusesMaxId(), '100');
        $cursor->setStatusesMaxId('100');

        $this->assertSame(['400', '300', '200'], $cursor->toArray()['statusesSeenMaxIds']);
        $this->assertTrue($cursor->hasSeenStatusesMaxId('200'));
    }

    public function test_bluesky_checkpoint_keeps_scoped_hashes_and_detects_a_cycle_after_reload(): void
    {
        $checkpoint = [
            'feedCursor' => 'page-b',
            'feedPage' => 2,
            'followersCursor' => '',
            'followersPage' => 0,
            'followsCursor' => '',
            'followsPage' => 0,
            'interactionPostIndex' => 0,
            'interactionKind' => 'likes',
            'interactionCursor' => '',
            'nextAdvanceAt' => 50,
            'seenCursors' => ['feed' => [hash('sha256', 'page-a') => true]],
            'replyFrontier' => ['at://root', 'at://branch'],
            'replyVisited' => ['at://previous-root'],
        ];
        $cursor = BlueskyParserCursorDTO::fromArray($this->reload($checkpoint));
        $this->assertSame($checkpoint, $cursor->toArray());
        try {
            $cursor->rememberPage('feed', $cursor->feedCursor(), 'page-a');
            $this->fail('A persisted earlier feed cursor must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Bluesky pagination cursor did not advance.', $exception->getMessage());
        }
        $this->assertSame($checkpoint, $cursor->toArray());

        $cursor->rememberPage('followers', null, 'page-a');

        $this->assertSame(['feed' => [hash('sha256', 'page-a') => true], 'followers' => []], $cursor->toArray()['seenCursors']);
    }

    public function test_bluesky_interaction_history_resets_per_post_while_feed_history_remains(): void
    {
        $cursor = new BlueskyParserCursorDTO;
        $cursor->rememberPage('feed', 'same-token', 'feed-next');
        $cursor->rememberPage('interaction:likes', 'same-token', 'likes-next');
        $cursor->rememberPage('interaction:reposts', 'same-token', 'reposts-next');
        $cursor->resetInteraction(1);
        $cursor = BlueskyParserCursorDTO::fromArray($this->reload($cursor->toArray()));

        $cursor->rememberPage('interaction:likes', 'same-token', 'likes-next');

        $this->assertSame(1, $cursor->interactionPostIndex());
        $this->assertSame([
            'feed' => [hash('sha256', 'same-token') => true],
            'interaction:likes' => [hash('sha256', 'same-token') => true],
        ], $cursor->toArray()['seenCursors']);
    }

    public function test_a_failed_bluesky_transition_does_not_create_a_new_history_scope(): void
    {
        $cursor = new BlueskyParserCursorDTO;
        try {
            $cursor->rememberPage('followers', 'same-token', 'same-token');
            $this->fail('A repeated page must be rejected before updating history.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Bluesky pagination cursor did not advance.', $exception->getMessage());
        }

        $this->assertSame([], $cursor->toArray()['seenCursors']);
    }

    private function reload(array $checkpoint): array
    {
        return json_decode(json_encode($checkpoint, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }
}
