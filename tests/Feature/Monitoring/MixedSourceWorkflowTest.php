<?php

namespace Tests\Feature\Monitoring;

use App\Jobs\Monitoring\BuildMonitoringReport;
use App\Jobs\Monitoring\CollectMonitoringSource;
use App\Jobs\Monitoring\ExportMonitoringReport;
use App\Jobs\Monitoring\ValidateMonitoringSource;
use App\Models\FeatureUsageDaily;
use App\Models\MonitoringCollection;
use App\Models\MonitoringMaterial;
use App\Models\MonitoringReport;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Jobs\DeliverBotMessage;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Services\Monitoring\MonitoringCollector;
use App\Services\Monitoring\MonitoringExports;
use App\Services\Monitoring\MonitoringManager;
use App\Services\Monitoring\MonitoringScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Feature\TelegramBot\TelegramBotTestCase;
use Tests\Support\FakeTrackingGateway;

class MixedSourceWorkflowTest extends TelegramBotTestCase
{
    private FakeTrackingGateway $telegram;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-06T09:00:00Z'));
        Bus::fake([ValidateMonitoringSource::class, CollectMonitoringSource::class, BuildMonitoringReport::class, ExportMonitoringReport::class]);
        config([
            'monitoring.initial_lookback_days' => 1, 'monitoring.report_wait_seconds' => 0,
            'monitoring.request_gap_seconds' => 0, 'monitoring.page_size' => 100,
            'services.youtube' => ['key' => 'test-key', 'base_url' => 'https://www.googleapis.com/youtube/v3', 'retry_attempts' => 0],
            'services.bluesky' => ['identifier' => 'test', 'app_password' => 'test', 'pds_url' => 'https://bsky.social', 'retry_attempts' => 0],
            'services.mastodon' => ['token' => 'test', 'base_url' => 'https://mastodon.social', 'retry_attempts' => 0],
            'osint.news_media_intel.searxng' => ['base_url' => 'http://127.0.0.1:8088', 'max_pages' => 3, 'language' => 'en'],
        ]);
        $this->telegram = new FakeTrackingGateway;
        $this->app->instance(TrackingGateway::class, $this->telegram);
    }

    public function test_five_real_adapters_share_durable_project_report_exports_history_and_bot_summary(): void
    {
        $link = $this->linkedUser(preferences: ['notifications_enabled' => true, 'exports_enabled' => true]);
        $user = $link->user;
        $user->subscriptions()->create(['plan' => 'pro', 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        $this->fakePlatforms();
        $manager = app(MonitoringManager::class);
        $project = $manager->create($user, ['name' => 'Five monitored sources', 'language' => 'en', 'timezone' => 'UTC', 'delivery_enabled' => true]);
        $inputs = ['telegram' => '@publicgroup', 'youtube' => 'UCabcdefghijklmnopqrstuv',
            'bluesky' => 'author.bsky.social', 'mastodon' => 'author@remote.example', 'news' => 'OpenAI site:news.example.com'];
        foreach ($inputs as $platform => $input) {
            $manager->addSource($project->fresh(), compact('platform', 'input'));
        }
        // Resolve against the current project generation, as recovered queue jobs do.
        foreach ($project->sources()->get() as $source) {
            $source->load('project');
            app()->call([new ValidateMonitoringSource($source->id, $user->id, $source->generation, $source->project->generation), 'handle']);
            $this->assertSame('ready', $source->fresh()->status, $source->platform.' must use a working adapter.');
        }
        $schedule = $manager->saveSchedule($project->fresh(), ['period' => 'day', 'time' => '09:00', 'timezone' => 'UTC', 'delivery_enabled' => true]);
        $schedule->update(['next_run_at' => now()]);
        $this->telegram->pages = [[['_' => 'message', 'id' => 12, 'message' => 'Telegram publication',
            'date' => CarbonImmutable::parse('2026-10-05T12:00:00Z')->timestamp, 'from_id' => ['_' => 'peerUser', 'user_id' => 42]]]];

        app(MonitoringScheduler::class)->tick();
        app(MonitoringScheduler::class)->tick();
        $this->assertDatabaseCount('monitoring_reports', 1);
        $this->assertDatabaseCount('monitoring_collections', 5);
        $report = MonitoringReport::query()->sole();
        foreach (MonitoringCollection::query()->orderBy('id')->get() as $collection) {
            $this->completeCollection($collection, $user->id);
        }
        app()->call([new BuildMonitoringReport($report->id, $user->id), 'handle']);
        $report->refresh();
        $this->assertContains($report->status, ['completed', 'partial']);
        $this->assertSame(5, $report->summary['count']);
        $this->assertEqualsCanonicalizing(['telegram' => 1, 'youtube' => 1, 'bluesky' => 1, 'mastodon' => 1, 'news' => 1], $report->summary['platform_counts']);
        $this->assertSame(5, $report->items()->count());
        $this->assertSame(5, count($report->coverage));
        $this->assertSame(5, MonitoringMaterial::query()->count());
        $this->assertSame(5, (int) FeatureUsageDaily::query()->where('feature', 'monitoring.material')->value('used'));
        $this->assertSame(1, (int) FeatureUsageDaily::query()->where('feature', 'monitoring.report')->value('used'));
        $this->assertDatabaseCount('parser_runs', 0);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        $this->assertSame(0, FeatureUsageDaily::query()->where('feature', 'like', '%.parser')->count());
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'commentThreads') || str_contains($request->url(), 'getFollowers'));
        Bus::assertDispatched(ValidateMonitoringSource::class);
        Bus::assertDispatched(CollectMonitoringSource::class);
        Bus::assertDispatched(BuildMonitoringReport::class);
        $externalCalls = Http::recorded()->count();

        app()->call([new ExportMonitoringReport($report->id, $user->id), 'handle']);
        $report->refresh();
        $this->assertSame('ready', $report->file_status);
        $exports = app(MonitoringExports::class);
        $json = $exports->artifact($report, 'json');
        $snapshot = json_decode(file_get_contents($json['path']), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('free-search.monitoring.v1', $snapshot['schema']);
        $this->assertSame(5, count($snapshot['materials']));
        $this->assertEqualsCanonicalizing(array_keys($inputs), array_column($snapshot['materials'], 'platform'));
        $this->assertStringNotContainsString('test-key', json_encode($snapshot));
        $this->assertStringNotContainsString('session_name', json_encode($snapshot));
        $xlsx = $exports->artifact($report, 'xlsx');
        $workbook = IOFactory::load($xlsx['path']);
        $this->assertSame(6, $workbook->getSheet(1)->getHighestRow());
        $workbook->disconnectWorksheets();
        $this->actingAs($user)->get('/monitoring/history?project='.$project->id)->assertOk();
        $this->get('/monitoring/reports/'.$report->id)->assertOk();
        $this->get('/monitoring/reports/'.$report->id.'/download/json')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/monitoring/reports/'.$report->id.'/download/xlsx')->assertOk();

        $delivery = BotDelivery::query()->sole();
        $this->assertSame('monitoring_digest', $delivery->kind);
        Queue::assertPushed(DeliverBotMessage::class, 1);
        $this->mock(BotTransport::class)->shouldReceive('message')->once()->with($link->telegraph_chat_id,
            Mockery::on(function (BotScreen $screen): bool {
                foreach (['Telegram publication', 'YouTube video', 'Bluesky publication', 'Mastodon publication', 'News article'] as $excerpt) {
                    $this->assertStringContainsString($excerpt, $screen->text);
                }

                return true;
            }));
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        $this->assertSame('sent', $report->fresh()->delivery_status);
        $this->assertDatabaseCount('telegram_bot_deliveries', 1);
        $this->assertSame($externalCalls, Http::recorded()->count(), 'Saved reports/downloads/delivery must never collect sources again.');

        MonitoringMaterial::query()->update(['text' => 'Later edit']);
        $manager->removeSource($project->sources()->where('platform', 'youtube')->firstOrFail());
        $this->assertSame($snapshot, json_decode(file_get_contents($json['path']), true, flags: JSON_THROW_ON_ERROR));
        $this->assertStringContainsString('YouTube video', json_encode($report->items()->pluck('snapshot')->all()));
    }

    public function test_repeated_news_without_publication_date_retains_first_collection_time_and_single_identity(): void
    {
        $link = $this->linkedUser();
        Http::fake(['127.0.0.1:8088/search' => function (Request $request) {
            return Http::response(['results' => (int) $request['pageno'] === 1 ? [
                ['title' => 'Unknown date article', 'url' => 'https://news.example.com/unknown', 'content' => 'Article excerpt'],
            ] : []]);
        }]);
        $manager = app(MonitoringManager::class);
        $project = $manager->create($link->user, ['name' => 'Unknown dates', 'timezone' => 'UTC']);
        $source = $manager->addSource($project, ['platform' => 'news', 'input' => 'OpenAI site:news.example.com']);
        $source->load('project');
        app(MonitoringCollector::class)->validate($source->id, $link->user_id, $source->generation, $source->project->generation);
        app(MonitoringScheduler::class)->tick();
        $collection = $source->collections()->sole();
        $this->completeCollection($collection, $link->user_id);
        $material = MonitoringMaterial::query()->sole();
        $first = $material->collected_at;
        $this->assertNull($material->published_at);
        $this->assertContains('publication_date_unavailable', $collection->fresh()->warnings);
        $this->travel(1)->days();
        app(MonitoringScheduler::class)->tick();
        $next = $source->collections()->latest('id')->firstOrFail();
        $this->completeCollection($next, $link->user_id);

        $this->assertDatabaseCount('monitoring_materials', 1);
        $this->assertNull($material->fresh()->published_at);
        $this->assertTrue($material->fresh()->collected_at->equalTo($first));
        $this->assertSame(1, (int) FeatureUsageDaily::query()->where('feature', 'monitoring.material')->sum('used'));
        $observed = $manager->requestReport($project->fresh(), 'day', 'first-observation-day');
        app()->call([new BuildMonitoringReport($observed->id, $link->user_id), 'handle']);
        $this->assertSame(1, $observed->fresh()->summary['unknown_date_count']);
        $this->assertNull($observed->items()->sole()->snapshot['published_at']);
        $this->travel(1)->days();
        $later = $manager->requestReport($project->fresh(), 'day', 'later-observation-day');
        app()->call([new BuildMonitoringReport($later->id, $link->user_id), 'handle']);
        $this->assertSame(0, $later->fresh()->summary['count'], 'An unchanged undated article must not become new on every check.');
    }

    private function completeCollection(MonitoringCollection $collection, int $owner): void
    {
        for ($step = 0; $step < 5; $step++) {
            app()->call([new CollectMonitoringSource($collection->id, $owner), 'handle']);
            $collection->refresh();
            if (in_array($collection->status, ['completed', 'partial'], true)) {
                return;
            }
            $this->travel(3)->seconds();
        }
        $this->fail('The bounded fake collection must reach a terminal result: '.$collection->error);
    }

    private function fakePlatforms(): void
    {
        $channel = 'UCabcdefghijklmnopqrstuv';
        $did = 'did:plc:workflowauthor';
        $account = ['id' => '42', 'acct' => 'author@remote.example', 'url' => 'https://remote.example/@author'];
        Http::fake([
            'www.googleapis.com/youtube/v3/channels*' => Http::response(['items' => [[
                'id' => $channel, 'snippet' => ['title' => 'YouTube channel'], 'contentDetails' => ['relatedPlaylists' => ['uploads' => 'UUworkflow']],
            ]]]),
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response(['items' => [[
                'contentDetails' => ['videoId' => 'workflowvideo', 'videoPublishedAt' => '2026-10-05T12:00:00Z'],
            ]]]),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => [[
                'id' => 'workflowvideo', 'snippet' => ['channelId' => $channel, 'channelTitle' => 'Channel', 'title' => 'YouTube video',
                    'description' => 'Video description', 'publishedAt' => '2026-10-05T12:00:00Z'], 'status' => ['privacyStatus' => 'public'], 'statistics' => [],
            ]]]),
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::response(['accessJwt' => 'workflow-token']),
            'bsky.social/xrpc/app.bsky.actor.getProfiles*' => Http::response(['profiles' => [['did' => $did, 'handle' => 'author.bsky.social']]]),
            'bsky.social/xrpc/app.bsky.feed.getAuthorFeed*' => Http::response(['feed' => [['post' => [
                'uri' => 'at://'.$did.'/app.bsky.feed.post/workflow', 'author' => ['did' => $did, 'handle' => 'author.bsky.social'],
                'record' => ['text' => 'Bluesky publication', 'createdAt' => '2026-10-05T12:00:00Z'],
            ]]]]),
            'mastodon.social/api/v1/accounts/lookup*' => Http::response($account),
            'mastodon.social/api/v1/accounts/42/statuses*' => Http::response([[
                'id' => '99', 'uri' => 'https://remote.example/users/author/statuses/99', 'url' => 'https://remote.example/@author/99',
                'content' => '<p>Mastodon publication</p>', 'created_at' => '2026-10-05T12:00:00Z', 'visibility' => 'public', 'account' => $account,
            ]]),
            '127.0.0.1:8088/search' => function (Request $request) {
                return Http::response(['results' => (int) $request['pageno'] === 1 ? [[
                    'title' => 'News article', 'url' => 'https://news.example.com/workflow', 'content' => 'News excerpt', 'publishedDate' => '2026-10-05T12:00:00Z',
                ]] : []]);
            },
        ]);
    }
}
