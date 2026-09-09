<?php

namespace Tests\Feature\TelegramBot;

use App\Models\User;
use App\Modules\TelegramBot\Application\AccountLinkService;
use App\Modules\TelegramBot\Infrastructure\NotificationText;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Models\LinkRequest;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;

final class AccountLinkNotificationTest extends TelegramBotTestCase
{
    public function test_cancelling_an_unconfirmed_request_does_not_notify(): void
    {
        $user = User::factory()->create();
        app(AccountLinkService::class)->issue($user, 'en');

        app(AccountLinkService::class)->disconnect($user);

        $this->assertDatabaseCount('telegram_bot_link_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
        Queue::assertNothingPushed();
    }

    public function test_relinking_creates_a_new_security_event_without_time_based_suppression(): void
    {
        $user = User::factory()->create();
        $this->confirmLink($user);
        app(AccountLinkService::class)->disconnect($user);
        $this->confirmLink($user);

        $notifications = $user->unreadNotifications()->get();
        $this->assertCount(3, $notifications);
        $this->assertSame(2, $notifications->where('data.title_key', 'systemNotifications.telegramBotLinked.title')->count());
        $this->assertSame(1, $notifications->where('data.title_key', 'systemNotifications.telegramBotUnlinked.title')->count());
        $this->assertSame(1, BotDelivery::query()->count());
    }

    public function test_unlink_notification_is_saved_on_site_without_delivery_to_the_disconnected_chat(): void
    {
        $link = $this->linkedUser();
        app(AccountLinkService::class)->disconnect($link->user);

        $notification = $link->user->unreadNotifications()->sole();
        $this->assertSame('systemNotifications.telegramBotUnlinked.body', $notification->data['body_key']);
        $this->assertSame('telegram_bot_unlinked', $notification->data['meta']['event']);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        Queue::assertNothingPushed();
        Telegraph::assertNothingSent();
    }

    public function test_link_and_notification_are_rolled_back_together(): void
    {
        $user = User::factory()->create();
        $service = app(AccountLinkService::class);
        $token = Str::after($service->issue($user, 'en'), '?start=');
        $this->assertTrue($service->claim($token, '10001', $this->chat()->id));
        $request = LinkRequest::query()->sole();

        try {
            DB::transaction(function () use ($service, $user, $request): void {
                $service->confirm($user, $request->id);
                throw new RuntimeException('Rollback confirmation');
            });
            $this->fail('The transaction must roll back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback confirmation', $exception->getMessage());
        }

        $this->assertNull($user->fresh()->telegram_id);
        $this->assertModelExists($request);
        $this->assertDatabaseCount('telegram_bot_links', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        Queue::assertNothingPushed();
    }

    public function test_unlink_and_notification_are_rolled_back_together(): void
    {
        $link = $this->linkedUser();

        try {
            DB::transaction(function () use ($link): void {
                app(AccountLinkService::class)->disconnect($link->user);
                throw new RuntimeException('Rollback disconnection');
            });
            $this->fail('The transaction must roll back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback disconnection', $exception->getMessage());
        }

        $this->assertModelExists($link);
        $this->assertSame($link->telegram_id, $link->user->fresh()->telegram_id);
        $this->assertDatabaseCount('notifications', 0);
        Queue::assertNothingPushed();
    }

    public function test_link_notifications_use_ru_and_en_localization_with_telegram_id(): void
    {
        $user = User::factory()->create();
        $this->confirmLink($user);
        app(AccountLinkService::class)->disconnect($user);
        $renderer = app(NotificationText::class);

        foreach ($user->notifications()->get() as $notification) {
            $this->assertArrayNotHasKey('title', $notification->data);
            $this->assertArrayNotHasKey('body', $notification->data);
            $english = $renderer->render($notification->data, 'en');
            $russian = $renderer->render($notification->data, 'ru');
            $this->assertNotSame($english, $russian);
            foreach ([$english, $russian] as $text) {
                $this->assertStringContainsString('10001', $text);
                $this->assertStringNotContainsString('{telegramId}', $text);
            }
        }
    }

    private function confirmLink(User $user): BotLink
    {
        $service = app(AccountLinkService::class);
        $token = Str::after($service->issue($user, 'en'), '?start=');
        $chat = $this->bot->chats()->firstOrCreate(['chat_id' => '10001'], ['name' => 'Private chat']);
        $this->assertTrue($service->claim($token, '10001', $chat->id));

        return $service->confirm($user, LinkRequest::query()->sole()->id);
    }
}
