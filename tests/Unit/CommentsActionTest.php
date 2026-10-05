<?php

namespace Tests\Unit;

use App\Modules\Telegram\Actions\Request\CommentsAction;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CommentsActionTest extends TestCase
{
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

        $page = $action->page(['_' => 'messages.channelMessages', 'messages' => [['id' => 5]], 'count' => 1]);

        $this->assertSame(5, $page['next_offset_id']);
        $this->assertTrue($page['has_more']);
    }

    public function test_repeated_comment_offsets_cannot_create_endless_pagination(): void
    {
        $action = new TestableCommentsAction;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Telegram comment pagination did not advance.');

        $action->page(['_' => 'messages.channelMessages', 'messages' => [['id' => 5]]], 5);
    }
}

class TestableCommentsAction extends CommentsAction
{
    public function page(array $response, int $offset = 0): array
    {
        return $this->loadComments(static fn (array $parameters): array => $response, [], 1, 20, 1, $offset, 0);
    }
}
