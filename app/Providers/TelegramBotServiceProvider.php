<?php

namespace App\Providers;

use App\Models\ParserRun;
use App\Modules\TelegramBot\Application\ArtifactRegistry;
use App\Modules\TelegramBot\Application\BotRouter;
use App\Modules\TelegramBot\Console\BroadcastBotMessage;
use App\Modules\TelegramBot\Console\ConfigureBotWebhook;
use App\Modules\TelegramBot\Console\MaintainBotDeliveries;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\Contracts\BotAction;
use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Infrastructure\BotEventSubscriber;
use App\Modules\TelegramBot\Infrastructure\TelegraphTransport;
use App\Modules\TelegramBot\Jobs\BotJob;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

final class TelegramBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BotConfig::class);
        $this->app->bind(BotTransport::class, TelegraphTransport::class);
        $this->app->tag(config('telegram_bot.actions', []), BotAction::class);
        $this->app->tag(config('telegram_bot.artifact_providers', []), ArtifactProvider::class);
        $this->app->bind(BotRouter::class, fn ($app) => new BotRouter($app->tagged(BotAction::class)));
        $this->app->bind(ArtifactRegistry::class, fn ($app) => new ArtifactRegistry($app->tagged(ArtifactProvider::class)));
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/telegram-bot.php'));
        $this->commands([ConfigureBotWebhook::class, BroadcastBotMessage::class, MaintainBotDeliveries::class]);

        Event::listen(NotificationSent::class, [BotEventSubscriber::class, 'notificationSent']);
        ParserRun::saved(fn (ParserRun $run) => $this->app->make(BotEventSubscriber::class)->parserSaved($run));

        RateLimiter::for('telegram-bot', fn (BotJob $job) => [
            Limit::perSecond(max(1, (int) config('telegram_bot.messages_per_second')))->by('telegram-bot:global'),
            Limit::perSecond(1)->by('telegram-bot:chat:'.$job->telegramId),
        ]);
        foreach (config('telegram_bot.http_limits') as $key => $limit) {
            RateLimiter::for('telegram-bot-'.$key, fn (Request $request) => Limit::perMinute($limit)->by((string) $request->user()?->id));
        }
        Schedule::command('telegram-bot:maintain')->everyMinute()->withoutOverlapping();
        Schedule::command('telegram-bot:maintain --prune')->dailyAt(config('telegram_bot.cleanup_time'))->withoutOverlapping();
    }
}
