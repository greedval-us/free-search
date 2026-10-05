<?php

namespace Tests\Feature;

use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use App\Modules\Bluesky\Parser\BlueskyParserCollector;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class BlueskyParserCollectorTest extends TestCase
{
    public static function paginatedStages(): array
    {
        return [
            'feed' => ['feed', 'likes', 'getAuthorFeed', 'feed', 'feedCursor'],
            'followers' => ['followers', 'likes', 'getFollowers', 'followers', 'followersCursor'],
            'follows' => ['follows', 'likes', 'getFollows', 'follows', 'followsCursor'],
            'likes' => ['interactions', 'likes', 'getLikes', 'likes', 'interactionCursor'],
            'reposts' => ['interactions', 'reposts', 'getRepostedBy', 'repostedBy', 'interactionCursor'],
        ];
    }

    #[DataProvider('paginatedStages')]
    public function test_a_repeated_cursor_refuses_to_advance_in_every_paginated_stage(string $stage, string $kind, string $method, string $list, string $cursorKey): void
    {
        $gateway = $this->createMock(BlueskyGatewayInterface::class);
        $gateway->expects($this->once())->method($method)->willReturn([$list => [], 'cursor' => 'same']);
        $run = $this->initialRun($stage, $kind);
        $run['cursor'][$cursorKey] = 'same';
        $collector = $this->collector($gateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bluesky pagination cursor did not advance.');

        $collector->advance($run);
    }

    #[DataProvider('paginatedStages')]
    public function test_missing_page_lists_cannot_be_mistaken_for_a_completed_stage(string $stage, string $kind, string $method, string $list, string $cursorKey): void
    {
        $gateway = $this->createMock(BlueskyGatewayInterface::class);
        $gateway->expects($this->once())->method($method)->willReturn([]);
        $collector = $this->collector($gateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bluesky returned an invalid page.');

        $collector->advance($this->initialRun($stage, $kind));
    }

    public function test_a_cursor_cycle_is_detected_after_serializing_checkpoints(): void
    {
        $gateway = $this->createMock(BlueskyGatewayInterface::class);
        $gateway->expects($this->exactly(3))->method('getAuthorFeed')->willReturnOnConsecutiveCalls(
            ['feed' => [], 'cursor' => 'first'], ['feed' => [], 'cursor' => 'second'], ['feed' => [], 'cursor' => 'first'],
        );
        $collector = $this->collector($gateway);
        $run = $collector->advance($this->initialRun('feed'));
        $run = $collector->advance(json_decode(json_encode($run), true));
        $this->assertSame('second', $run['cursor']['feedCursor']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bluesky pagination cursor did not advance.');

        $collector->advance(json_decode(json_encode($run), true));
    }

    public function test_valid_empty_lists_finish_the_run_without_fetching_unrelated_threads(): void
    {
        $gateway = $this->createMock(BlueskyGatewayInterface::class);
        $gateway->expects($this->once())->method('getAuthorFeed')->willReturn(['feed' => []]);
        $gateway->expects($this->once())->method('getFollowers')->willReturn(['followers' => []]);
        $gateway->expects($this->once())->method('getFollows')->willReturn(['follows' => []]);
        $gateway->expects($this->never())->method('getPostThread');
        $collector = $this->collector($gateway);
        $run = $this->initialRun('feed');
        $run['data']['postsIndex'] = [];

        for ($step = 0; $step < 5; $step++) {
            $run = $collector->advance($run);
        }

        $this->assertSame('completed', $run['status']);
        $this->assertSame([], $run['result']['postsIndex']);
        $this->assertSame([], $run['result']['receivedRepliesIndex']);
    }

    public function test_reply_frontiers_resume_beyond_depth_six_and_keep_the_original_root(): void
    {
        $root = $this->uri(0);
        $boundary = $this->uri(6);
        $thread = $this->node($boundary, 1);
        for ($depth = 5; $depth >= 0; $depth--) {
            $thread = $this->node($this->uri($depth), 1, [$thread]);
        }
        $targets = [];
        $gateway = $this->createMock(BlueskyGatewayInterface::class);
        $gateway->expects($this->exactly(2))->method('getPostThread')->willReturnCallback(function (string $uri, int $depth, int $parentHeight) use (&$targets, $root, $boundary, $thread): array {
            $targets[] = $uri;
            $this->assertSame(6, $depth);
            $this->assertSame(0, $parentHeight);

            return ['thread' => $uri === $root ? $thread : $this->node($boundary, 1, [$this->node($this->uri(7))])];
        });
        $collector = $this->collector($gateway);

        $run = $collector->advance($this->initialRun('interactions', 'replies'));
        $this->assertSame('interactions', $run['stage']);
        $this->assertSame([$boundary], $run['cursor']['replyFrontier']);
        $this->assertSame(6, $run['stats']['processedReceivedReplies']);
        $run = $collector->advance(json_decode(json_encode($run), true));

        $this->assertSame([$root, $boundary], $targets);
        $this->assertSame('finishing', $run['stage']);
        $this->assertSame(7, $run['stats']['processedReceivedReplies']);
        $this->assertSame([$root], array_values(array_unique(array_column($run['data']['receivedRepliesIndex'], 'rootPostUri'))));
        $this->assertSame(7, count(array_unique(array_column($run['data']['receivedRepliesIndex'], 'uri'))));
    }

    public static function unavailableThreads(): array
    {
        return [
            'missing payload' => [[]],
            'deleted post' => [['thread' => ['$type' => 'app.bsky.feed.defs#notFoundPost', 'notFound' => true]]],
            'blocked post' => [['thread' => ['$type' => 'app.bsky.feed.defs#blockedPost', 'blocked' => true]]],
        ];
    }

    #[DataProvider('unavailableThreads')]
    public function test_unavailable_threads_cannot_complete_as_empty_success(array $response): void
    {
        $gateway = $this->createMock(BlueskyGatewayInterface::class);
        $gateway->expects($this->once())->method('getPostThread')->willReturn($response);
        $collector = $this->collector($gateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bluesky reply thread is unavailable.');

        $collector->advance($this->initialRun('interactions', 'replies'));
    }

    public function test_missing_reported_replies_fail_without_advancing_the_interaction_post(): void
    {
        $gateway = $this->createMock(BlueskyGatewayInterface::class);
        $gateway->expects($this->once())->method('getPostThread')->willReturn(['thread' => $this->node($this->uri(0), 3)]);
        $collector = $this->collector($gateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bluesky reply thread is incomplete.');

        $collector->advance($this->initialRun('interactions', 'replies'));
    }

    public function test_a_cyclic_reply_graph_cannot_be_exported_as_complete(): void
    {
        $gateway = $this->createMock(BlueskyGatewayInterface::class);
        $gateway->expects($this->once())->method('getPostThread')->willReturn([
            'thread' => $this->node($this->uri(0), 1, [$this->node($this->uri(0))]),
        ]);
        $collector = $this->collector($gateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bluesky reply thread contains a cycle.');

        $collector->advance($this->initialRun('interactions', 'replies'));
    }

    private function collector(BlueskyGatewayInterface $gateway): BlueskyParserCollector
    {
        $this->app->instance(BlueskyGatewayInterface::class, $gateway);

        return app(BlueskyParserCollector::class);
    }

    private function initialRun(string $stage, string $kind = 'likes'): array
    {
        return ['runId' => 'bsky-test', 'userId' => 1, 'status' => 'running', 'stage' => $stage,
            'context' => ['actor' => 'writer.bsky.social'], 'cursor' => ['interactionKind' => $kind],
            'data' => ['profile' => ['did' => 'did:plc:writer'], 'postsIndex' => [['uri' => $this->uri(0), 'cid' => 'cid']]]];
    }

    private function uri(int $index): string
    {
        return 'at://did:plc:writer/app.bsky.feed.post/'.$index;
    }

    private function node(string $uri, int $replyCount = 0, array $replies = []): array
    {
        return ['$type' => 'app.bsky.feed.defs#threadViewPost', 'post' => [
            'uri' => $uri, 'cid' => 'cid', 'author' => ['did' => 'did:plc:writer', 'handle' => 'writer.bsky.social'],
            'record' => ['text' => 'Public reply', 'createdAt' => '2026-10-01T00:00:00Z'], 'replyCount' => $replyCount,
        ], 'replies' => $replies];
    }
}
