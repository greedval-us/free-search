<?php

namespace Tests\Feature;

use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Parser\YouTubeParserRunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Controllers\Concerns\CreatesPaidUser;
use Tests\TestCase;

class YouTubeParserReliabilityTest extends TestCase
{
    use CreatesPaidUser;
    use RefreshDatabase;

    public function test_all_comment_and_reply_pages_survive_checkpoints_without_duplicates(): void
    {
        Storage::fake('private');
        $this->freezeTime();
        config()->set('osint.parser_runs.queue.enabled', false);
        $user = $this->paidUser();
        $first = $this->thread('thread-1', 'comment-1', 3, [['id' => 'reply-1', 'snippet' => ['textDisplay' => 'embedded']]]);
        $second = $this->thread('thread-2', 'comment-2', 1);
        $this->mock(YouTubeGatewayInterface::class, function ($mock) use ($first, $second): void {
            $mock->shouldReceive('commentThreads')->once()->with(['videoId' => 'pilot-video', 'maxResults' => 100])
                ->andReturn(['items' => [$first], 'nextPageToken' => 'comments-2']);
            $mock->shouldReceive('commentThreads')->once()->with(['videoId' => 'pilot-video', 'maxResults' => 100, 'pageToken' => 'comments-2'])
                ->andReturn(['items' => [$first, $second]]);
            $mock->shouldReceive('comments')->once()->with(['parentId' => 'comment-1', 'maxResults' => 100])
                ->andReturn(['items' => [$this->reply('reply-1'), $this->reply('reply-2')], 'nextPageToken' => 'replies-2']);
            $mock->shouldReceive('comments')->once()->with(['parentId' => 'comment-1', 'maxResults' => 100, 'pageToken' => 'replies-2'])
                ->andReturn(['items' => [], 'nextPageToken' => 'replies-3']);
            $mock->shouldReceive('comments')->once()->with(['parentId' => 'comment-1', 'maxResults' => 100, 'pageToken' => 'replies-3'])
                ->andReturn(['items' => [$this->reply('reply-3')]]);
            $mock->shouldReceive('comments')->once()->with(['parentId' => 'comment-2', 'maxResults' => 100])
                ->andReturn(['items' => [], 'nextPageToken' => 'replies-2']);
            $mock->shouldReceive('comments')->once()->with(['parentId' => 'comment-2', 'maxResults' => 100, 'pageToken' => 'replies-2'])
                ->andReturn(['items' => [$this->reply('reply-4')]]);
        });
        $run = app(YouTubeParserRunStore::class)->create($user->id, ['videoId' => 'pilot-video']);
        $this->actingAs($user);

        for ($step = 0; $step < 8; $step++) {
            $response = $this->getJson(route('youtube.parser.status', ['runId' => $run['runId']]))->assertOk();
            $this->travel(3)->seconds();
        }

        $response->assertJsonPath('status', 'completed')
            ->assertJsonPath('processedComments', 2)->assertJsonPath('processedReplies', 4);
        $payload = json_decode($this->get(route('youtube.parser.download-json', ['runId' => $run['runId']]))->assertOk()->streamedContent(), true);
        $this->assertSame(['comment-1', 'comment-2'], array_column($payload['commentsIndex'], 'commentId'));
        $this->assertSame(['reply-1', 'reply-2', 'reply-3', 'reply-4'], array_column($payload['repliesIndex'], 'replyId'));
    }

    public function test_repeating_comment_cursor_fails_with_partial_export_instead_of_looping(): void
    {
        Storage::fake('private');
        $this->freezeTime();
        config()->set('osint.parser_runs.queue.enabled', false);
        $user = $this->paidUser();
        $this->mock(YouTubeGatewayInterface::class, function ($mock): void {
            $mock->shouldReceive('commentThreads')->once()->with(['videoId' => 'pilot-video', 'maxResults' => 100])
                ->andReturn(['items' => [$this->thread('thread-1', 'comment-1')], 'nextPageToken' => 'A']);
            $mock->shouldReceive('commentThreads')->once()->with(['videoId' => 'pilot-video', 'maxResults' => 100, 'pageToken' => 'A'])
                ->andReturn(['items' => [], 'nextPageToken' => 'B']);
            $mock->shouldReceive('commentThreads')->once()->with(['videoId' => 'pilot-video', 'maxResults' => 100, 'pageToken' => 'B'])
                ->andReturn(['items' => [], 'nextPageToken' => 'A']);
        });
        $run = app(YouTubeParserRunStore::class)->create($user->id, ['videoId' => 'pilot-video']);
        $this->actingAs($user);

        for ($step = 0; $step < 3; $step++) {
            $response = $this->getJson(route('youtube.parser.status', ['runId' => $run['runId']]))->assertOk();
            $this->travel(3)->seconds();
        }

        $response->assertJsonPath('status', 'failed')->assertJsonPath('processedComments', 1);
        $this->assertNotNull($response->json('downloadJsonUrl'));
        $payload = json_decode($this->get(route('youtube.parser.download-json', ['runId' => $run['runId']]))->assertOk()->streamedContent(), true);
        $this->assertSame(1, $payload['commentsCount']);
        $this->getJson(route('youtube.parser.status', ['runId' => $run['runId']]))->assertJsonPath('status', 'failed');
    }

    public function test_repeating_reply_cursor_cannot_mark_run_as_complete(): void
    {
        Storage::fake('private');
        $this->freezeTime();
        config()->set('osint.parser_runs.queue.enabled', false);
        $user = $this->paidUser();
        $this->mock(YouTubeGatewayInterface::class, function ($mock): void {
            $mock->shouldReceive('commentThreads')->once()->andReturn(['items' => [$this->thread('thread-1', 'comment-1', 5)]]);
            $mock->shouldReceive('comments')->once()->with(['parentId' => 'comment-1', 'maxResults' => 100])
                ->andReturn(['items' => [$this->reply('reply-1')], 'nextPageToken' => 'A']);
            $mock->shouldReceive('comments')->once()->with(['parentId' => 'comment-1', 'maxResults' => 100, 'pageToken' => 'A'])
                ->andReturn(['items' => [], 'nextPageToken' => 'A']);
        });
        $run = app(YouTubeParserRunStore::class)->create($user->id, ['videoId' => 'pilot-video']);
        $this->actingAs($user);

        for ($step = 0; $step < 3; $step++) {
            $response = $this->getJson(route('youtube.parser.status', ['runId' => $run['runId']]))->assertOk();
            $this->travel(3)->seconds();
        }

        $response->assertJsonPath('status', 'failed')->assertJsonPath('processedReplies', 1);
        $this->assertNotNull($response->json('downloadJsonUrl'));
    }

    private function thread(string $threadId, string $commentId, int $replyCount = 0, array $replies = []): array
    {
        return [
            'id' => $threadId,
            'snippet' => ['videoId' => 'pilot-video', 'totalReplyCount' => $replyCount,
                'topLevelComment' => ['id' => $commentId, 'snippet' => ['textDisplay' => $commentId]]],
            'replies' => ['comments' => $replies],
        ];
    }

    private function reply(string $id): array
    {
        return ['id' => $id, 'snippet' => ['textDisplay' => $id]];
    }

    public static function malformedPages(): array
    {
        return ['comments' => [false], 'replies' => [true]];
    }

    #[DataProvider('malformedPages')]
    public function test_malformed_page_preserves_previous_results_and_fails(bool $replies): void
    {
        Storage::fake('private');
        $this->freezeTime();
        config()->set('osint.parser_runs.queue.enabled', false);
        $user = $this->paidUser();
        $this->mock(YouTubeGatewayInterface::class, function ($mock) use ($replies): void {
            $mock->shouldReceive('commentThreads')->once()->with(['videoId' => 'pilot-video', 'maxResults' => 100])
                ->andReturn($replies
                    ? ['items' => [$this->thread('thread-1', 'comment-1', 1)]]
                    : ['items' => [$this->thread('thread-1', 'comment-1')], 'nextPageToken' => 'A']);
            $mock->shouldReceive($replies ? 'comments' : 'commentThreads')->once()->andReturn(['items' => null]);
        });
        $run = app(YouTubeParserRunStore::class)->create($user->id, ['videoId' => 'pilot-video']);
        $this->actingAs($user);

        $this->getJson(route('youtube.parser.status', ['runId' => $run['runId']]))->assertOk();
        $this->travel(3)->seconds();
        $response = $this->getJson(route('youtube.parser.status', ['runId' => $run['runId']]))->assertOk();

        $response->assertJsonPath('status', 'failed')->assertJsonPath('processedComments', 1);
        $payload = json_decode($this->get(route('youtube.parser.download-json', ['runId' => $run['runId']]))->assertOk()->streamedContent(), true);
        $this->assertSame(['status' => 'failed', 'complete' => false], $payload['collection']);
        $this->assertSame(1, $payload['commentsCount']);
    }
}
