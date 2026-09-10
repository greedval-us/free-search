<?php

namespace Tests\Feature\TelegramBot;

use App\Integrations\TelegramBot\TrackingArtifactProvider;
use App\Models\TelegramTracking;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\Telegram\Tracking\TrackingCollector;
use App\Modules\Telegram\Tracking\TrackingService;
use App\Modules\TelegramBot\Application\ArtifactRegistry;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Infrastructure\NotificationText;
use App\Modules\TelegramBot\Jobs\DeliverBotMessage;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeTrackingGateway;

final class TrackingIntegrationTest extends TelegramBotTestCase
{
    private FakeTrackingGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        config(['telegram_tracking.queue.connection' => 'database']);
        $this->gateway = new FakeTrackingGateway;
        $this->app->instance(TrackingGateway::class, $this->gateway);
    }

    public static function notificationPreferences(): array
    {
        return [[false, true, false], [true, false, false], [true, true, true]];
    }

    #[DataProvider('notificationPreferences')]
    public function test_bell_always_receives_matches_and_bot_requires_both_preferences(bool $taskPreference, bool $linkPreference, bool $delivered): void
    {
        $link = $this->linkedUser(preferences: ['notifications_enabled' => $linkPreference]);
        $this->tracking($link, $taskPreference);
        $this->assertSame(1, $link->user->notifications()->count());
        $this->assertDatabaseCount('telegram_bot_deliveries', $delivered ? 1 : 0);
        $data = $link->user->notifications()->first()->data;
        $english = app(NotificationText::class)->render($data, 'en');
        $russian = app(NotificationText::class)->render($data, 'ru');
        $this->assertStringContainsString('found 1', $english);
        $this->assertStringContainsString('найдено сообщений 1', $russian);
    }

    public function test_tracking_report_provider_is_registered_and_manual_file_is_delivered(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => false]);
        $task = $this->tracking($link);
        $this->assertInstanceOf(TrackingArtifactProvider::class, app(ArtifactRegistry::class)->get('tracking'));
        $provider = app(TrackingArtifactProvider::class);
        $this->assertSame($task->id, $provider->listing($link->user_id, 1)->items()[0]['id']);
        $this->assertTrue(app(DeliveryOutbox::class)->enqueue($link, 'tracking', (string) $task->id, ['format' => 'json'], false, 'manual-download'));
        $this->mock(BotTransport::class)->shouldReceive('document')->once()->with($link->telegraph_chat_id,
            \Mockery::on(function (BotDocument $document) use ($task): bool {
                $data = json_decode(file_get_contents($document->path), true, flags: JSON_THROW_ON_ERROR);

                return $data['tracking']['id'] === $task->id && count($data['messages']) === 1;
            }));
        $delivery = BotDelivery::query()->sole();
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    public function test_foreign_expired_and_unsupported_tracking_reports_are_rejected(): void
    {
        $link = $this->linkedUser();
        $task = $this->tracking($link);
        $provider = app(TrackingArtifactProvider::class);
        foreach ([[$link->user_id + 1, 'json'], [$link->user_id, '../env']] as [$userId, $format]) {
            try {
                $provider->document($userId, $task->id, $format, 'en');
                $this->fail('Unavailable artifact must not be generated.');
            } catch (ArtifactUnavailable) {
                $this->addToAssertionCount(1);
            }
        }
        $document = $provider->document($link->user_id, $task->id, 'xlsx', 'ru');
        $this->assertFileExists($document->path);
        app(TemporaryDocuments::class)->remove($document);
        $task->update(['purge_at' => now()->subSecond()]);
        $this->assertCount(0, $provider->listing($link->user_id, 1)->items());
        $this->expectException(ArtifactUnavailable::class);
        $provider->document($link->user_id, $task->id, 'json', 'en');
    }

    private function tracking(BotLink $link, bool $notifyBot = false): TelegramTracking
    {
        $task = app(TrackingService::class)->create($link->user, [
            'name' => 'My tracking', 'mode' => 'keyword', 'query' => 'test', 'groups' => ['publicgroup'], 'notify_bot' => $notifyBot,
        ]);
        $this->travel(6)->hours();
        $source = $task->sources()->first();
        $source->update(['lease_token' => 'tracking-test']);
        $this->gateway->pages = [[['_' => 'message', 'id' => 1, 'message' => 'test message', 'date' => now()->subMinute()->timestamp]]];
        app(TrackingCollector::class)->collect($source->id, 'tracking-test');
        $source->refresh()->update(['lease_token' => 'tracking-test-final']);
        app(TrackingCollector::class)->collect($source->id, 'tracking-test-final');

        return $task;
    }
}
