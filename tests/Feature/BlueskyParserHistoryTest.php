<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Bluesky\Parser\BlueskyParserRunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreatesSubscribedUser;
use Tests\TestCase;

class BlueskyParserHistoryTest extends TestCase
{
    use CreatesSubscribedUser;
    use RefreshDatabase;

    public function test_history_endpoint_returns_current_users_recent_bluesky_exports(): void
    {
        Storage::fake('private');

        config()->set('osint.parser_runs.retention_days', 7);
        config()->set('osint.parser_runs.history_limit', 20);

        $user = $this->createSubscribedUser();
        $otherUser = User::factory()->create();
        $store = app(BlueskyParserRunStore::class);

        $ownRun = $store->create($user->id, [
            'actor' => 'analyst.bsky.social',
        ]);

        $store->mutate($user->id, $ownRun['runId'], function (array $run): array {
            $run['status'] = 'completed';
            $run['stage'] = 'completed';
            $run['progress'] = 100;
            $run['stats']['processedPosts'] = 9;
            $run['stats']['processedAuthoredReplies'] = 3;
            $run['stats']['processedReceivedReplies'] = 4;
            $run['stats']['processedFollowers'] = 20;
            $run['stats']['processedFollows'] = 12;
            $run['stats']['processedReactions'] = 15;
            $run['result'] = [
                'actor' => 'analyst.bsky.social',
                'posts' => [],
            ];

            return $run;
        });

        $foreignRun = $store->create($otherUser->id, [
            'actor' => 'hidden.bsky.social',
        ]);

        $store->mutate($otherUser->id, $foreignRun['runId'], function (array $run): array {
            $run['status'] = 'completed';
            $run['stage'] = 'completed';
            $run['progress'] = 100;
            $run['result'] = [
                'actor' => 'hidden.bsky.social',
                'posts' => [],
            ];

            return $run;
        });

        $this->actingAs($user)
            ->getJson(route('bluesky.parser.history'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('retentionDays', 7)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.runId', $ownRun['runId'])
            ->assertJsonPath('items.0.actor', 'analyst.bsky.social')
            ->assertJsonPath('items.0.downloadable', true)
            ->assertJsonPath('items.0.processedPosts', 9)
            ->assertJsonPath('items.0.processedAuthoredReplies', 3)
            ->assertJsonPath('items.0.processedReceivedReplies', 4)
            ->assertJsonPath('items.0.processedFollowers', 20)
            ->assertJsonPath('items.0.processedFollows', 12)
            ->assertJsonPath('items.0.processedReactions', 15);
    }
}
