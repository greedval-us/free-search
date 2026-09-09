<?php

namespace Tests\Feature\TelegramBot;

use App\Modules\TelegramBot\Application\AccountLinkService;
use App\Modules\TelegramBot\Application\ArtifactRegistry;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Domain\Exceptions\TelegramTransportException;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Infrastructure\NotificationText;
use App\Modules\TelegramBot\Jobs\DeliverBotMessage;
use App\Modules\TelegramBot\Jobs\Middleware\TelegramCooldown;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Notifications\SystemDatabaseNotification;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

final class DeliveryTest extends TelegramBotTestCase
{
    public function test_database_notification_is_delivered_once_through_telegraph(): void
    {
        $link = $this->linkedUser();
        DB::transaction(fn () => $link->user->notify(new SystemDatabaseNotification(['title' => 'Hello', 'body' => 'Account update'])));
        $delivery = BotDelivery::query()->sole();
        $this->assertSame('notification', $delivery->kind);
        Queue::assertPushed(DeliverBotMessage::class);
        $job = new DeliverBotMessage($delivery->id, $link->telegram_id);
        app()->call([$job, 'handle']);
        app()->call([$job, 'handle']);
        Telegraph::assertSent("Hello\n\nAccount update");
        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->sent_at);
        app(DeliveryOutbox::class)->enqueue($link, 'notification', $delivery->reference);
        $this->assertDatabaseCount('telegram_bot_deliveries', 1);
        Queue::assertPushed(DeliverBotMessage::class, 1);
    }

    public function test_unsubscribing_before_execution_prevents_delivery(): void
    {
        $link = $this->linkedUser();
        app(DeliveryOutbox::class)->enqueue($link, 'notification', 'missing');
        $delivery = BotDelivery::query()->sole();
        $link->update(['notifications_enabled' => false]);
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        Telegraph::assertNothingSent();
        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
    }

    public function test_reports_cannot_be_queued_even_with_file_delivery_enabled(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $outbox = app(DeliveryOutbox::class);

        foreach ([true, false] as $automatic) {
            $this->assertFalse($outbox->enqueue($link, 'report', '1', ['format' => 'html'], $automatic));
        }

        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        Queue::assertNothingPushed();
        Telegraph::assertNothingSent();
    }

    public function test_legacy_pending_reports_are_skipped_without_sending(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);

        foreach ([true, false] as $automatic) {
            $delivery = BotDelivery::query()->create([
                'link_id' => $link->id,
                'deduplication_key' => hash('sha256', 'legacy-report:'.(int) $automatic),
                'kind' => 'report',
                'reference' => '1',
                'payload' => ['format' => 'html'],
                'automatic' => $automatic,
            ]);

            app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
            $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        }

        Telegraph::assertNothingSent();
    }

    public function test_disconnect_removes_queued_deliveries_and_blocking_prevents_new_ones(): void
    {
        $link = $this->linkedUser();
        app(DeliveryOutbox::class)->enqueue($link, 'notification', 'missing');
        $job = new DeliverBotMessage(BotDelivery::query()->sole()->id, $link->telegram_id);
        app(AccountLinkService::class)->disconnect($link->user);
        app()->call([$job, 'handle']);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        $blocked = $this->linkedUser('10002');
        $blocked->user->update(['is_blocked' => true]);
        $this->assertFalse(app(DeliveryOutbox::class)->enqueue($blocked->fresh(), 'notification', 'missing'));
        Telegraph::assertNothingSent();
    }

    public function test_notification_text_reuses_localization_and_interpolates_parameters(): void
    {
        $renderer = app(NotificationText::class);
        $data = ['title_key' => 'systemNotifications.loginGreeting.title', 'body_key' => 'systemNotifications.loginGreeting.body', 'body_params' => ['ip' => '192.0.2.1']];
        $english = $renderer->render($data, 'en');
        $russian = $renderer->render($data, 'ru');
        $this->assertStringContainsString('192.0.2.1', $english);
        $this->assertStringContainsString('192.0.2.1', $russian);
        $this->assertStringNotContainsString('{ip}', $russian);
        $this->assertNotSame($english, $russian);
    }

    public function test_parser_completion_is_opt_in_and_uploads_using_existing_export_builder(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $this->completedRun($link->user_id);
        $delivery = BotDelivery::query()->sole();
        $this->assertSame('parser', $delivery->kind);
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        Telegraph::assertSentData('sendDocument', ['chat_id' => '10001'], false);
        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
        $other = $this->linkedUser('10002');
        $this->completedRun($other->user_id);
        $this->assertDatabaseCount('telegram_bot_deliveries', 1);
    }

    public function test_telegram_forbidden_marks_delivery_failed_without_retrying_forever(): void
    {
        $link = $this->linkedUser(preferences: ['broadcasts_enabled' => true]);
        app(DeliveryOutbox::class)->enqueue($link, 'broadcast', 'campaign', ['message' => 'Update']);
        $delivery = BotDelivery::query()->sole();
        $this->mock(BotTransport::class)->shouldReceive('message')->once()->andThrow(new TelegramTransportException(403));
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        $this->assertSame(BotDelivery::FAILED, $delivery->fresh()->status);
        $this->assertSame('telegram_403', $delivery->fresh()->error_code);
    }

    #[DataProvider('accessRevokedDuringRendering')]
    public function test_access_is_rechecked_after_rendering_and_temporary_documents_are_removed(bool $automatic, bool $blocked): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        app(DeliveryOutbox::class)->enqueue($link, 'parser', '1', ['format' => 'json'], $automatic);
        $delivery = BotDelivery::query()->sole();
        $provider = $this->mock(ArtifactProvider::class);
        $provider->shouldReceive('key')->andReturn('parser');
        $provider->shouldReceive('document')->once()->andReturnUsing(function () use ($link, $blocked): BotDocument {
            $document = app(TemporaryDocuments::class)->create('test.json', function (string $path): void {
                Storage::disk('local')->put($path, '{}');
            });
            if ($blocked) {
                $link->user->update(['is_blocked' => true]);
            } else {
                $link->update(['exports_enabled' => false]);
            }

            return $document;
        });
        $this->instance(ArtifactRegistry::class, new ArtifactRegistry([$provider]));

        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
        Telegraph::assertNothingSent();
    }

    public static function accessRevokedDuringRendering(): array
    {
        return [
            'automatic export opted out' => [true, false],
            'automatic export user blocked' => [true, true],
            'manual export user blocked' => [false, true],
        ];
    }

    #[DataProvider('unavailableDocumentConsent')]
    public function test_unavailable_file_notice_rechecks_consent(bool $optedOut): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        app(DeliveryOutbox::class)->enqueue($link, 'parser', '1', ['format' => 'json']);
        $delivery = BotDelivery::query()->sole();
        $provider = $this->mock(ArtifactProvider::class);
        $provider->shouldReceive('key')->andReturn('parser');
        $provider->shouldReceive('document')->once()->andReturnUsing(function () use ($link, $optedOut): never {
            if ($optedOut) {
                $link->update(['exports_enabled' => false]);
            }
            throw new ArtifactUnavailable;
        });
        $this->instance(ArtifactRegistry::class, new ArtifactRegistry([$provider]));

        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        $this->assertSame('file_unavailable', $delivery->fresh()->error_code);
        if ($optedOut) {
            Telegraph::assertNothingSent();
        } else {
            Telegraph::assertSent(__('telegram_bot.errors.file_unavailable', [], $link->locale));
        }
    }

    public static function unavailableDocumentConsent(): array
    {
        return [
            'notice allowed' => [false],
            'consent revoked during lookup' => [true],
        ];
    }

    public function test_queue_failure_preserves_outbox_and_does_not_fail_website_notification(): void
    {
        $link = $this->linkedUser();
        Bus::shouldReceive('dispatch')->andThrow(new \RuntimeException('Queue temporarily unavailable'));
        DB::transaction(fn () => $link->user->notify(new SystemDatabaseNotification(['title' => 'Hello', 'body' => 'Saved'])));
        $this->assertDatabaseCount('notifications', 1);
        $delivery = BotDelivery::query()->sole();
        $this->assertSame(BotDelivery::PENDING, $delivery->status);
        $this->assertNull($delivery->dispatched_at);
    }

    public function test_retry_after_pauses_all_bot_jobs_instead_of_sleeping_inside_worker(): void
    {
        $job = (new DeliverBotMessage(1, '10001'))->withFakeQueueInteractions();
        $middleware = new TelegramCooldown;
        $middleware->handle($job, fn () => throw new TelegramTransportException(429, 45));
        $job->assertReleased(45);
        $middleware->handle($job, fn () => $this->fail('Cooldown must prevent another API call.'));
        $job->assertReleased(45);
    }
}
