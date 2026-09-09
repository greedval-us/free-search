<?php

namespace Tests\Feature\TelegramBot;

use App\Models\User;
use App\Modules\TelegramBot\Application\AccountLinkService;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Models\LinkRequest;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

final class AccountLinkTest extends TelegramBotTestCase
{
    public function test_settings_are_available_without_a_bot_but_link_creation_is_disabled(): void
    {
        config()->set('telegram_bot.enabled', false);
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('telegram-bot.settings'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/Telegram')->where('telegram.available', false));
        $this->postJson(route('telegram-bot.link.issue'))->assertStatus(503);
        $this->assertDatabaseCount('telegram_bot_link_requests', 0);
    }

    public function test_link_requires_bot_claim_and_explicit_confirmation_by_the_same_website_user(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson(route('telegram-bot.link.issue'))->assertOk();
        $token = Str::after($response->json('data.url'), '?start=');
        $request = LinkRequest::query()->sole();
        $this->assertSame(hash('sha256', $token), $request->token_hash);
        $this->assertStringNotContainsString('token_hash', $response->getContent());
        $this->assertNull($request->telegram_id);
        $this->postJson(route('telegram-bot.link.confirm'), ['request_id' => $request->id])->assertUnprocessable();

        $chat = $this->chat();
        $this->assertTrue(app(AccountLinkService::class)->claim($token, '10001', $chat->id));
        $this->assertDatabaseCount('telegram_bot_links', 0);
        $this->assertNull($user->fresh()->telegram_id);
        $this->getJson(route('telegram-bot.settings.status'))->assertJsonPath('data.pending.telegram_id', '10001');
        $this->postJson(route('telegram-bot.link.confirm'), ['request_id' => $request->id])->assertOk()->assertJsonPath('data.link.telegram_id', '10001');
        $this->assertSame('10001', $user->fresh()->telegram_id);
        $this->assertDatabaseCount('telegram_bot_link_requests', 0);
        $this->assertFalse(app(AccountLinkService::class)->claim($token, '10001', $chat->id));
        $link = BotLink::query()->sole();
        $this->assertFalse($link->exports_enabled);
        $this->assertFalse($link->broadcasts_enabled);
    }

    public function test_expired_and_replaced_tokens_cannot_be_claimed(): void
    {
        $user = User::factory()->create();
        $service = app(AccountLinkService::class);
        $old = Str::after($service->issue($user, 'ru'), '?start=');
        $new = Str::after($service->issue($user, 'ru'), '?start=');
        $chat = $this->chat();
        $this->assertFalse($service->claim($old, '10001', $chat->id));
        $this->travel(11)->minutes();
        $this->assertFalse($service->claim($new, '10001', $chat->id));
    }

    public function test_claimed_telegram_identity_cannot_be_swapped_before_confirmation(): void
    {
        $service = app(AccountLinkService::class);
        $token = Str::after($service->issue(User::factory()->create(), 'en'), '?start=');
        $this->assertTrue($service->claim($token, '10001', $this->chat()->id));
        $this->assertFalse($service->claim($token, '10002', $this->chat('10002')->id));
        $this->assertSame('10001', LinkRequest::query()->sole()->telegram_id);
    }

    public function test_foreign_website_user_cannot_confirm_a_request(): void
    {
        app(AccountLinkService::class)->issue(User::factory()->create(), 'en');
        $request = LinkRequest::query()->sole();
        $this->actingAs(User::factory()->create())->postJson(route('telegram-bot.link.confirm'), ['request_id' => $request->id])->assertUnprocessable();
        $this->assertDatabaseCount('telegram_bot_links', 0);
    }

    public function test_existing_telegram_account_cannot_be_stolen_by_another_site_user(): void
    {
        $this->linkedUser();
        $service = app(AccountLinkService::class);
        $token = Str::after($service->issue(User::factory()->create(), 'en'), '?start=');
        $chat = $this->bot->chats()->where('chat_id', '10001')->firstOrFail();
        $this->assertFalse($service->claim($token, '10001', $chat->id));
    }

    public function test_link_cannot_use_a_different_bots_chat_or_mismatched_private_id(): void
    {
        $service = app(AccountLinkService::class);
        $token = Str::after($service->issue(User::factory()->create(), 'en'), '?start=');
        $chat = $this->chat('10002');
        $this->assertFalse($service->claim($token, '10001', $chat->id));
        config()->set('telegram_bot.bot_id', $this->bot->id + 1);
        $this->assertFalse($service->claim($token, '10002', $chat->id));
    }

    public function test_guests_unverified_and_blocked_users_cannot_link(): void
    {
        $this->postJson(route('telegram-bot.link.issue'))->assertUnauthorized();
        $this->actingAs(User::factory()->unverified()->create())->postJson(route('telegram-bot.link.issue'))->assertForbidden();
        $this->actingAs(User::factory()->create(['is_blocked' => true]))->postJson(route('telegram-bot.link.issue'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('telegram_bot_link_requests', 0);
    }

    public function test_preferences_and_disconnect_only_affect_the_authenticated_link(): void
    {
        $own = $this->linkedUser();
        $other = $this->linkedUser('10002');
        $this->actingAs($own->user)->patchJson(route('telegram-bot.settings.preferences'), [
            'locale' => 'ru', 'notifications_enabled' => false, 'exports_enabled' => true, 'broadcasts_enabled' => true,
            'telegram_id' => '10002', 'user_id' => $other->user_id,
        ])->assertOk()->assertJsonPath('data.link.telegram_id', '10001')->assertJsonPath('data.link.locale', 'ru');
        $this->assertFalse($other->fresh()->exports_enabled);
        $this->deleteJson(route('telegram-bot.link.disconnect'))->assertOk()->assertJsonPath('data.link', null);
        $this->assertNull($own->user->fresh()->telegram_id);
        $this->assertNotNull($other->fresh());
    }
}
