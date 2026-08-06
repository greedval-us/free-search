<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Mastodon\Parser\MastodonParserRunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Controllers\Concerns\CreatesPaidUser;
use Tests\TestCase;

class MastodonParserHistoryTest extends TestCase
{
    use CreatesPaidUser;
    use RefreshDatabase;

    public function test_history_endpoint_returns_current_users_recent_mastodon_exports(): void
    {
        Storage::fake('private');

        config()->set('osint.parser_runs.retention_days', 7);
        config()->set('osint.parser_runs.history_limit', 20);

        $user = $this->paidUser();
        $otherUser = User::factory()->create();
        $store = app(MastodonParserRunStore::class);

        $ownRun = $store->create($user->id, [
            'account' => '@analyst@mastodon.social',
        ]);

        $store->mutate($user->id, $ownRun['runId'], function (array $run): array {
            $run['status'] = 'completed';
            $run['stage'] = 'completed';
            $run['progress'] = 100;
            $run['stats']['processedStatuses'] = 14;
            $run['stats']['processedComments'] = 6;
            $run['result'] = [
                'account' => '@analyst@mastodon.social',
                'statuses' => [],
                'comments' => [],
            ];

            return $run;
        });

        $foreignRun = $store->create($otherUser->id, [
            'account' => '@hidden@example.social',
        ]);

        $store->mutate($otherUser->id, $foreignRun['runId'], function (array $run): array {
            $run['status'] = 'completed';
            $run['stage'] = 'completed';
            $run['progress'] = 100;
            $run['result'] = [
                'account' => '@hidden@example.social',
                'statuses' => [],
                'comments' => [],
            ];

            return $run;
        });

        $this->actingAs($user)
            ->getJson(route('mastodon.parser.history'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('retentionDays', 7)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.runId', $ownRun['runId'])
            ->assertJsonPath('items.0.account', '@analyst@mastodon.social')
            ->assertJsonPath('items.0.downloadable', true)
            ->assertJsonPath('items.0.processedStatuses', 14)
            ->assertJsonPath('items.0.processedComments', 6);
    }
}
