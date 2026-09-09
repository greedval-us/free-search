<?php

namespace App\Modules\TelegramBot\Console;

use App\Modules\TelegramBot\Support\BotConfig;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Console\Command;
use Throwable;

final class ConfigureBotWebhook extends Command
{
    protected $signature = 'telegram-bot:configure {--check : Validate settings without calling Telegram}';

    protected $description = 'Configure the isolated website bot webhook (without placing its token in the URL)';

    public function handle(BotConfig $config): int
    {
        $connection = $config->get('queue.connection');
        $driver = config('queue.connections.'.$connection.'.driver');
        $timeout = $config->integer('queue.timeout');
        $retryAfter = (int) config('queue.connections.'.$connection.'.retry_after', $timeout + 1);
        $bot = $config->botId() > 0 ? TelegraphBot::query()->find($config->botId()) : null;
        $url = route('telegram-bot.webhook');
        if (! $config->active() || $bot === null || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || ! in_array($driver, ['database', 'redis', 'beanstalkd', 'sqs'], true)
            || in_array(config('cache.default'), ['array', 'null'], true)
            || $retryAfter <= $timeout || (int) config('telegraph.http_timeout') >= $timeout) {
            $this->error('Check bot settings, HTTPS APP_URL, persistent queue/cache and HTTP timeout < job timeout < retry_after.');

            return self::FAILURE;
        }
        if ($this->option('check')) {
            $this->info('Bot configuration is valid. No Telegram API requests were made.');

            return self::SUCCESS;
        }
        try {
            $response = $bot->withEndpoint('setWebhook')->withData('url', $url)
                ->withData('secret_token', $config->secret())
                ->withData('allowed_updates', ['message', 'callback_query'])->send();
            if ($response->failed() || $response->telegraphError()) {
                $this->error('Telegram rejected the webhook configuration. API code: '.(int) $response->json('error_code'));

                return self::FAILURE;
            }
        } catch (Throwable) {
            $this->error('Could not contact Telegram. Credentials were not printed.');

            return self::FAILURE;
        }
        $this->info('Webhook configured. Start the bot and link your account in settings/telegram.');

        return self::SUCCESS;
    }
}
