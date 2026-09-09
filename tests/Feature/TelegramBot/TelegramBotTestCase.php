<?php

namespace Tests\Feature\TelegramBot;

use App\Models\User;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use App\Modules\TelegramBot\Models\BotLink;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class TelegramBotTestCase extends TestCase
{
    use RefreshDatabase;

    protected TelegraphBot $bot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Telegraph::fake();
        Queue::fake();
        Storage::fake('local');
        Storage::fake('private');
        $this->bot = TelegraphBot::query()->create(['token' => '123456:testing-not-a-real-token', 'name' => 'Test bot']);
        config()->set([
            'app.url' => 'https://example.test',
            'telegram_bot.enabled' => true,
            'telegram_bot.bot_id' => $this->bot->id,
            'telegram_bot.username' => 'uraboros_test_bot',
            'telegram_bot.webhook_secret' => str_repeat('s', 64),
            'telegram_bot.queue.connection' => 'database',
            'inertia.ssr.enabled' => false,
        ]);
    }

    protected function chat(string $telegramId = '10001'): TelegraphChat
    {
        return $this->bot->chats()->create(['chat_id' => $telegramId, 'name' => 'Private chat']);
    }

    protected function linkedUser(string $telegramId = '10001', array $preferences = []): BotLink
    {
        $user = User::factory()->create(['telegram_id' => $telegramId]);

        return BotLink::query()->create([
            'user_id' => $user->id,
            'telegram_id' => $telegramId,
            'telegraph_chat_id' => $this->chat($telegramId)->id,
            'locale' => 'en',
            ...$preferences,
        ])->refresh();
    }

    protected function completedRun(int $userId): string
    {
        $store = app(TelegramParserRunStore::class);
        $run = $store->create($userId, ['chatUsername' => 'publicchannel', 'keyword' => '', 'period' => 'day']);
        $store->mutate($userId, $run['runId'], function (array $run): array {
            return [...$run, 'status' => 'completed', 'stage' => 'completed', 'progress' => 100,
                'result' => ['chatUsername' => 'publicchannel', 'messages' => [], 'comments' => []]];
        });

        return $run['runId'];
    }
}
