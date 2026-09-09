<?php

namespace Tests\Feature\TelegramBot;

use App\Modules\TelegramBot\Application\BotRouter;
use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotUpdate;
use App\Modules\TelegramBot\Jobs\ProcessBotUpdate;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Facades\Queue;

final class WebhookAndMenuTest extends TelegramBotTestCase
{
    private function update(array $changes = []): array
    {
        return array_replace_recursive(['update_id' => 12, 'message' => [
            'text' => '/start', 'chat' => ['id' => 10001, 'type' => 'private'],
            'from' => ['id' => 10001, 'is_bot' => false, 'language_code' => 'ru'],
        ]], $changes);
    }

    public function test_webhook_requires_configuration_and_constant_secret_header(): void
    {
        $url = route('telegram-bot.webhook');
        $this->assertStringNotContainsString($this->bot->token, $url);
        $this->postJson($url, $this->update())->assertForbidden();
        $this->postJson($url, $this->update(), ['X-Telegram-Bot-Api-Secret-Token' => 'bad'])->assertForbidden();
        config()->set('telegram_bot.webhook_secret', '');
        $this->postJson($url, $this->update())->assertNotFound();
        Queue::assertNothingPushed();
    }

    public function test_valid_private_updates_are_queued_and_link_tokens_are_encrypted_in_queue(): void
    {
        $this->postJson(route('telegram-bot.webhook'), $this->update(), ['X-Telegram-Bot-Api-Secret-Token' => str_repeat('s', 64)])->assertNoContent();
        Queue::assertPushed(ProcessBotUpdate::class, fn (ProcessBotUpdate $job) => $job instanceof ShouldBeEncrypted && $job->update->telegramId === '10001');
        Telegraph::assertNothingSent();
    }

    public function test_groups_forged_private_ids_bots_and_unsupported_updates_are_ignored(): void
    {
        foreach ([
            $this->update(['message' => ['chat' => ['type' => 'group']]]),
            $this->update(['message' => ['from' => ['id' => 10002]]]),
            $this->update(['message' => ['from' => ['is_bot' => true]]]),
            ['update_id' => 14, 'inline_query' => ['id' => 'anything']],
            ['update_id' => 'bad', 'message' => []],
        ] as $payload) {
            $this->postJson(route('telegram-bot.webhook'), $payload, ['X-Telegram-Bot-Api-Secret-Token' => str_repeat('s', 64)])->assertNoContent();
        }
        Queue::assertNothingPushed();
    }

    public function test_update_replay_is_ignored_and_unknown_actions_do_not_invoke_php_methods(): void
    {
        $update = BotUpdate::fromArray($this->update());
        $job = new ProcessBotUpdate($this->bot->id, $update);
        app()->call([$job, 'handle']);
        app()->call([$job, 'handle']);
        Telegraph::assertSent(__('telegram_bot.screens.unlinked', [], 'ru'));
        Telegraph::assertSentData('sendMessage', ['chat_id' => '10001'], false);
        $context = new BotContext(1, '10001', 'en', '12');
        $screen = app(BotRouter::class)->dispatch('__construct', $context);
        $this->assertSame(__('telegram_bot.screens.unlinked', [], 'en'), $screen->text);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
    }

    public function test_linked_menu_has_webapp_and_unlinked_users_cannot_request_files(): void
    {
        $link = $this->linkedUser();
        $context = new BotContext($link->telegraph_chat_id, $link->telegram_id, 'en', '12', $link->id, $link->user_id);
        $screen = app(BotRouter::class)->dispatch('menu', $context);
        $webapp = collect($screen->buttons)->firstWhere('type', 'webapp');
        $this->assertSame('https://example.test/dashboard', $webapp->value);
        $unlinked = new BotContext($link->telegraph_chat_id, '10001', 'en', '13');
        app(BotRouter::class)->dispatch('send', $unlinked, ['k' => 'parser', 'i' => '1', 'f' => 'xlsx']);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
    }

    public function test_menu_configuration_can_add_a_screen_without_changing_the_router(): void
    {
        config()->set('telegram_bot.menus.extra', [['label' => 'menu.back', 'action' => 'menu']]);
        $screen = app(BotRouter::class)->dispatch('menu', new BotContext(1, '10001', 'en', '1'), ['p' => 'extra']);
        $this->assertCount(1, $screen->buttons);
        $this->assertSame('menu', $screen->buttons[0]->value);
    }

    public function test_linked_menus_offer_parser_exports_without_reports_in_both_locales(): void
    {
        $link = $this->linkedUser();

        foreach (['ru', 'en'] as $locale) {
            $context = new BotContext($link->telegraph_chat_id, $link->telegram_id, $locale, '12', $link->id, $link->user_id);
            $screen = app(BotRouter::class)->dispatch('menu', $context);
            $files = collect($screen->buttons)->where('type', 'action')->where('value', 'files')->values();

            $this->assertCount(1, $files);
            $this->assertSame(['k' => 'parser'], $files[0]->parameters);
            $this->assertSame(__('telegram_bot.menu.exports', [], $locale), $files[0]->label);
        }
    }

    public function test_old_report_buttons_are_handled_without_queueing_downloads(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);

        foreach (['action:files;k:report', 'action:send;k:report;i:1;f:html'] as $index => $data) {
            $update = BotUpdate::fromArray([
                'update_id' => 20 + $index,
                'callback_query' => [
                    'id' => 'old-report-'.$index,
                    'from' => ['id' => (int) $link->telegram_id, 'is_bot' => false],
                    'message' => ['chat' => ['id' => (int) $link->telegram_id, 'type' => 'private']],
                    'data' => $data,
                ],
            ]);

            app()->call([new ProcessBotUpdate($this->bot->id, $update), 'handle']);
        }

        Telegraph::assertSent(__('telegram_bot.errors.file_unavailable', [], $link->locale));
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        Queue::assertNothingPushed();
    }
}
