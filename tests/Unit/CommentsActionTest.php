<?php

namespace Tests\Unit;

use App\Modules\Telegram\Actions\Request\CommentsAction;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CommentsActionTest extends TestCase
{
    public function test_broadcast_comments_use_the_linked_discussion_peer_and_forwarded_root(): void
    {
        $action = new TestableCommentsAction;
        $source = ['id' => 55, 'peer' => ['_' => 'inputPeerChannel', 'channel_id' => 55], 'chat' => ['broadcast' => true]];
        $discussion = ['id' => 77, 'peer' => ['_' => 'inputPeerChannel', 'channel_id' => 77]];

        $thread = $action->thread($source, 5, static fn () => $discussion, function (array $peer, int $postId) use ($source): array {
            $this->assertSame($source['peer'], $peer);
            $this->assertSame(5, $postId);

            return [
                '_' => 'messages.discussionMessage',
                'messages' => [
                    ['_' => 'message', 'id' => 31, 'peer_id' => -1000000000077],
                    ['_' => 'message', 'id' => 30, 'peer_id' => -1000000000077, 'fwd_from' => ['from_id' => -1000000000055, 'channel_post' => 5]],
                ],
            ];
        });

        $this->assertSame(['peer' => $discussion['peer'], 'id' => 30], $thread);
    }

    public function test_broadcast_discussion_content_is_not_requested_until_the_linked_group_is_public(): void
    {
        $action = new TestableCommentsAction;

        $this->expectException(RuntimeException::class);
        $action->thread(
            ['id' => 55, 'peer' => ['_' => 'inputPeerChannel', 'channel_id' => 55], 'chat' => ['broadcast' => true]],
            5,
            static fn () => null,
            fn () => $this->fail('A private discussion must not reach the discussion content RPC.'),
        );
    }

    public function test_broadcast_discussion_root_must_belong_to_the_authorized_group(): void
    {
        $action = new TestableCommentsAction;

        $this->expectException(RuntimeException::class);
        $action->thread(
            ['id' => 55, 'peer' => ['_' => 'inputPeerChannel', 'channel_id' => 55], 'chat' => ['broadcast' => true]],
            5,
            static fn () => ['id' => 77, 'peer' => ['_' => 'inputPeerChannel', 'channel_id' => 77]],
            static fn () => [
                '_' => 'messages.discussionMessage',
                'messages' => [
                    ['_' => 'message', 'id' => 30, 'peer_id' => -1000000000099, 'fwd_from' => ['from_id' => -1000000000055, 'channel_post' => 5]],
                ],
            ],
        );
    }

    public function test_megagroup_replies_use_the_source_without_loading_a_linked_discussion(): void
    {
        $action = new TestableCommentsAction;
        $peer = ['_' => 'inputPeerChannel', 'channel_id' => 55];
        $unexpected = fn () => $this->fail('A megagroup thread should be read in the source group.');

        $this->assertSame(['peer' => $peer, 'id' => 5], $action->thread(
            ['id' => 55, 'peer' => $peer, 'chat' => ['megagroup' => true]],
            5,
            $unexpected,
            $unexpected,
        ));
    }

    public static function malformedPages(): array
    {
        return [
            'missing messages' => [['_' => 'messages.channelMessages']],
            'null messages' => [['_' => 'messages.channelMessages', 'messages' => null]],
            'string messages' => [['_' => 'messages.channelMessages', 'messages' => 'invalid']],
            'associative messages' => [['_' => 'messages.channelMessages', 'messages' => ['post' => ['id' => 1]]]],
            'invalid item' => [['_' => 'messages.channelMessages', 'messages' => ['invalid']]],
            'missing item id' => [['_' => 'messages.channelMessages', 'messages' => [['message' => 'invalid']]]],
            'unknown response type' => [['_' => 'unknown', 'messages' => []]],
        ];
    }

    #[DataProvider('malformedPages')]
    public function test_malformed_comments_never_complete_as_an_empty_success(array $response): void
    {
        $action = new TestableCommentsAction;

        $this->expectException(RuntimeException::class);

        $action->page($response);
    }

    public function test_valid_empty_comments_finish_pagination(): void
    {
        $action = new TestableCommentsAction;

        $page = $action->page(['_' => 'messages.channelMessages', 'messages' => [], 'count' => 0]);

        $this->assertSame(['messages' => [], 'next_offset_id' => null, 'has_more' => false, 'total' => 0], $page);
    }

    public function test_short_comments_continue_with_the_source_offset_until_an_empty_page(): void
    {
        $action = new TestableCommentsAction;

        $page = $action->page(['_' => 'messages.channelMessages', 'messages' => [['id' => 5, 'peer_id' => -1000000000077]], 'count' => 1]);

        $this->assertSame(5, $page['next_offset_id']);
        $this->assertTrue($page['has_more']);
    }

    public function test_repeated_comment_offsets_cannot_create_endless_pagination(): void
    {
        $action = new TestableCommentsAction;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Telegram comment pagination did not advance.');

        $action->page(['_' => 'messages.channelMessages', 'messages' => [['id' => 5, 'peer_id' => -1000000000077]]], 5);
    }

    public function test_replies_from_another_peer_are_rejected(): void
    {
        $action = new TestableCommentsAction;

        $this->expectException(RuntimeException::class);
        $action->page(['_' => 'messages.channelMessages', 'messages' => [['id' => 5, 'peer_id' => -1000000000099]]]);
    }
}

class TestableCommentsAction extends CommentsAction
{
    public function thread(array $source, int $postId, Closure $resolveDiscussion, Closure $getDiscussionMessage): array
    {
        return $this->resolveThread($source, $postId, $resolveDiscussion, $getDiscussionMessage);
    }

    public function page(array $response, int $offset = 0): array
    {
        $peer = ['_' => 'inputPeerChannel', 'channel_id' => 77];

        return $this->loadComments(static fn (array $parameters): array => $response, $peer, 1, 20, 1, $offset, 0);
    }
}
