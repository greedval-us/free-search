<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\YouTube\Parser\YouTubeParserRunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Controllers\Concerns\CreatesPaidUser;
use Tests\TestCase;

class YouTubeParserHistoryTest extends TestCase
{
    use CreatesPaidUser;
    use RefreshDatabase;

    public function test_history_endpoint_returns_current_users_recent_youtube_exports(): void
    {
        Storage::fake('private');

        config()->set('osint.parser_runs.retention_days', 7);
        config()->set('osint.parser_runs.history_limit', 20);

        $user = $this->paidUser();
        $otherUser = User::factory()->create();
        $store = app(YouTubeParserRunStore::class);

        $ownRun = $store->create($user->id, [
            'videoId' => 'video-123',
        ]);

        $store->mutate($user->id, $ownRun['runId'], function (array $run): array {
            $run['status'] = 'completed';
            $run['stage'] = 'completed';
            $run['progress'] = 100;
            $run['stats']['processedComments'] = 11;
            $run['stats']['processedReplies'] = 5;
            $run['result'] = [
                'videoId' => 'video-123',
                'comments' => [],
                'replies' => [],
            ];

            return $run;
        });

        $foreignRun = $store->create($otherUser->id, [
            'videoId' => 'hidden-video',
        ]);

        $store->mutate($otherUser->id, $foreignRun['runId'], function (array $run): array {
            $run['status'] = 'completed';
            $run['stage'] = 'completed';
            $run['progress'] = 100;
            $run['result'] = [
                'videoId' => 'hidden-video',
                'comments' => [],
                'replies' => [],
            ];

            return $run;
        });

        $this->actingAs($user)
            ->getJson(route('youtube.parser.history'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('retentionDays', 7)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.runId', $ownRun['runId'])
            ->assertJsonPath('items.0.videoId', 'video-123')
            ->assertJsonPath('items.0.downloadable', true)
            ->assertJsonPath('items.0.processedComments', 11)
            ->assertJsonPath('items.0.processedReplies', 5);
    }
}
