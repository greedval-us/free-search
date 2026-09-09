<?php

namespace Tests\Feature\TelegramBot;

use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Domain\Exceptions\TelegramTransportException;
use App\Modules\TelegramBot\Jobs\DeliverBotMessage;
use App\Modules\TelegramBot\Models\BotDelivery;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

final class OperationsTest extends TelegramBotTestCase
{
    public function test_broadcast_requires_confirmation_and_respects_opt_in_and_locale(): void
    {
        $this->linkedUser(preferences: ['broadcasts_enabled' => true, 'locale' => 'en']);
        $this->linkedUser('10002', ['broadcasts_enabled' => false]);
        $this->linkedUser('10003', ['broadcasts_enabled' => true, 'locale' => 'ru']);
        $this->artisan('telegram-bot:broadcast', ['message' => 'Release', '--dry-run' => true])->assertSuccessful();
        $this->artisan('telegram-bot:broadcast', ['message' => 'Release'])->assertFailed();
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        $this->artisan('telegram-bot:broadcast', ['message' => 'Release', '--locale' => 'en', '--confirm' => true])->assertSuccessful();
        $this->assertDatabaseCount('telegram_bot_deliveries', 1);
        Telegraph::assertNothingSent();
    }

    public function test_webhook_configuration_check_never_calls_telegram_and_rejects_unsafe_queue(): void
    {
        URL::forceRootUrl('https://example.test');
        URL::forceScheme('https');
        config()->set('queue.connections.database.retry_after', 180);
        config()->set('cache.default', 'database');
        $this->artisan('telegram-bot:configure', ['--check' => true])->assertSuccessful();
        config()->set('telegram_bot.queue.connection', 'sync');
        $this->artisan('telegram-bot:configure', ['--check' => true])->assertFailed();
        config()->set('telegram_bot.queue.connection', 'missing');
        $this->artisan('telegram-bot:configure', ['--check' => true])->assertFailed();
        Telegraph::assertNothingSent();
    }

    public function test_scheduler_recovers_pending_delivery_without_a_live_api_call(): void
    {
        $link = $this->linkedUser();
        $delivery = BotDelivery::query()->create([
            'link_id' => $link->id, 'deduplication_key' => hash('sha256', 'retry'), 'kind' => 'notification', 'reference' => 'pending',
        ]);
        DB::transaction(fn () => $this->artisan('telegram-bot:maintain')->assertSuccessful());
        Queue::assertPushed(DeliverBotMessage::class, fn (DeliverBotMessage $job) => $job->deliveryId === $delivery->id);
        Telegraph::assertNothingSent();
    }

    public function test_transport_never_exposes_bot_token_when_package_throws(): void
    {
        $chat = $this->chat();
        Telegraph::shouldReceive('chat')->andThrow(new \RuntimeException('https://api.telegram.org/bot'.$this->bot->token.'/sendMessage'));
        try {
            app(BotTransport::class)->message($chat->id, new BotScreen('Test'));
            $this->fail('Transport must propagate a safe failure.');
        } catch (TelegramTransportException $exception) {
            $this->assertStringNotContainsString($this->bot->token, $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }
}
