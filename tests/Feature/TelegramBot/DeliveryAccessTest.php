<?php

namespace Tests\Feature\TelegramBot;

use App\Modules\TelegramBot\Application\BotAccess;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Jobs\DeliverBotMessage;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;

final class DeliveryAccessTest extends TelegramBotTestCase
{
    public function test_missing_link_is_denied_without_accessing_its_preferences(): void
    {
        $access = app(BotAccess::class);

        foreach (['notification', 'broadcast', 'parser'] as $kind) {
            foreach ([true, false] as $automatic) {
                $this->assertFalse($access->allowsDelivery(null, $kind, $automatic));
            }
        }
    }

    #[DataProvider('deliveryPreferences')]
    public function test_delivery_requires_access_and_the_relevant_consent(string $kind, bool $automatic, array $preferences, bool $accepted): void
    {
        $link = $this->linkedUser(preferences: [
            'notifications_enabled' => false,
            'exports_enabled' => false,
            'broadcasts_enabled' => false,
            ...$preferences,
        ]);

        $this->assertSame($accepted, app(DeliveryOutbox::class)->enqueue($link, $kind, 'test', automatic: $automatic));
        $this->assertDatabaseCount('telegram_bot_deliveries', $accepted ? 1 : 0);
        if ($accepted) {
            Queue::assertPushed(DeliverBotMessage::class, 1);
        } else {
            Queue::assertNothingPushed();
        }
        Telegraph::assertNothingSent();
    }

    public static function deliveryPreferences(): array
    {
        return [
            'notification opted in' => ['notification', true, ['notifications_enabled' => true], true],
            'notification opted out' => ['notification', true, [], false],
            'manual notification still needs consent' => ['notification', false, [], false],
            'file consent does not allow notifications' => ['notification', true, ['exports_enabled' => true], false],
            'broadcast opted in' => ['broadcast', true, ['broadcasts_enabled' => true], true],
            'broadcast opted out' => ['broadcast', true, [], false],
            'manual broadcast still needs consent' => ['broadcast', false, [], false],
            'notification consent does not allow broadcasts' => ['broadcast', true, ['notifications_enabled' => true], false],
            'automatic export opted in' => ['parser', true, ['exports_enabled' => true], true],
            'automatic export opted out' => ['parser', true, [], false],
            'manual export remains available' => ['parser', false, [], true],
            'manual tracking remains available' => ['tracking', false, [], true],
            'unsolicited tracking files rejected' => ['tracking', true, ['exports_enabled' => true], false],
            'notification consent does not allow exports' => ['parser', true, ['notifications_enabled' => true], false],
            'legacy automatic report rejected' => ['report', true, ['exports_enabled' => true], false],
            'legacy manual report rejected' => ['report', false, ['exports_enabled' => true], false],
            'unknown automatic kind rejected' => ['unknown', true, ['exports_enabled' => true], false],
            'unknown manual kind rejected' => ['unknown', false, ['exports_enabled' => true], false],
        ];
    }

    #[DataProvider('invalidAccess')]
    public function test_consent_and_manual_downloads_do_not_bypass_invalid_account_access(string $reason): void
    {
        $link = $this->linkedUser(preferences: [
            'notifications_enabled' => true,
            'exports_enabled' => true,
            'broadcasts_enabled' => true,
        ]);
        match ($reason) {
            'blocked' => $link->user->update(['is_blocked' => true]),
            'unverified' => $link->user->forceFill(['email_verified_at' => null])->save(),
            'different user identity' => $link->user->update(['telegram_id' => '10002']),
            'different chat identity' => $link->chat->update(['chat_id' => '10002']),
            'different bot' => config()->set('telegram_bot.bot_id', $this->bot->id + 1),
            'disabled bot' => config()->set('telegram_bot.enabled', false),
        };
        $link->refresh();

        foreach (['notification', 'broadcast', 'parser', 'tracking'] as $kind) {
            foreach ([true, false] as $automatic) {
                $this->assertFalse(app(DeliveryOutbox::class)->enqueue($link, $kind, 'test', automatic: $automatic));
            }
        }
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        Queue::assertNothingPushed();
        Telegraph::assertNothingSent();
    }

    public static function invalidAccess(): array
    {
        return [
            ['blocked'],
            ['unverified'],
            ['different user identity'],
            ['different chat identity'],
            ['different bot'],
            ['disabled bot'],
        ];
    }
}
