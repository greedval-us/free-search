<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Controllers\Concerns\CreatesPaidUser;
use Tests\TestCase;

class TelegramParserHistoryTest extends TestCase
{
    use CreatesPaidUser;
    use RefreshDatabase;

    public function test_history_endpoint_returns_current_users_recent_telegram_exports(): void
    {
        Storage::fake('private');

        config()->set('osint.parser_runs.retention_days', 7);
        config()->set('osint.parser_runs.history_limit', 20);

        $user = $this->paidUser();
        $otherUser = User::factory()->create();
        $store = app(TelegramParserRunStore::class);

        $ownRun = $store->create($user->id, [
            'chatUsername' => 'durov',
            'keyword' => 'osint',
            'period' => 'week',
        ]);

        $store->mutate($user->id, $ownRun['runId'], function (array $run): array {
            $run['status'] = 'completed';
            $run['stage'] = 'completed';
            $run['progress'] = 100;
            $run['stats']['processedMessages'] = 12;
            $run['stats']['processedComments'] = 4;
            $run['result'] = [
                'chatUsername' => 'durov',
                'messages' => [],
                'comments' => [],
            ];

            return $run;
        });

        $foreignRun = $store->create($otherUser->id, [
            'chatUsername' => 'foreign',
            'keyword' => 'hidden',
            'period' => 'day',
        ]);

        $store->mutate($otherUser->id, $foreignRun['runId'], function (array $run): array {
            $run['status'] = 'completed';
            $run['stage'] = 'completed';
            $run['progress'] = 100;
            $run['result'] = [
                'chatUsername' => 'foreign',
                'messages' => [],
                'comments' => [],
            ];

            return $run;
        });

        $this->actingAs($user)
            ->getJson(route('telegram.parser.history'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('retentionDays', 7)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.runId', $ownRun['runId'])
            ->assertJsonPath('items.0.chatUsername', 'durov')
            ->assertJsonPath('items.0.keyword', 'osint')
            ->assertJsonPath('items.0.period', 'week')
            ->assertJsonPath('items.0.downloadable', true)
            ->assertJsonPath('items.0.processedMessages', 12)
            ->assertJsonPath('items.0.processedComments', 4);
    }
}
